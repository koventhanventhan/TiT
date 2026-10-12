<?php

namespace App\Console\Commands;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\User;
use App\Services\Rag\GeminiRagClient;
use App\Services\Rag\RetrievalService;
use Illuminate\Console\Command;

class RagEvalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:eval {file : Path to the evaluation JSON file} {--institute=1 : Institute ID} {--live : Use real Gemini API client against live database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate RAG retrieval accuracy, score distribution, and threshold calibration';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $instituteId = (int) $this->option('institute');
        $isLive = (bool) $this->option('live');

        if (!file_exists($filePath)) {
            $this->error("Evaluation file not found at: {$filePath}");
            return self::FAILURE;
        }

        $rawJson = file_get_contents($filePath);
        $testCases = json_decode($rawJson, true);

        if (!is_array($testCases) || empty($testCases)) {
            $this->error("Evaluation file does not contain a valid non-empty JSON array.");
            return self::FAILURE;
        }

        if ($isLive) {
            $this->info("Running in --live mode using real Gemini API client...");
            app()->bind(GeminiClientInterface::class, GeminiRagClient::class);
        } else {
            $this->comment("Running using container-registered Gemini client (pass --live for real API).");
        }

        $retrievalService = app(RetrievalService::class);
        $this->info("Evaluating " . count($testCases) . " benchmark questions for Institute #{$instituteId}...\n");

        $tableRows = [];
        $answerableTotal = 0;
        $answerableHits = 0;
        $answerableScores = [];
        $negativeScores = [];
        $evalRecords = [];

        foreach ($testCases as $index => $case) {
            $question = (string) ($case['question'] ?? '');
            $grade = (string) ($case['grade'] ?? '');
            $medium = (string) ($case['medium'] ?? 'all');
            $subject = isset($case['subject']) ? (string) $case['subject'] : null;
            $shouldAnswer = isset($case['should_answer']) ? (bool) $case['should_answer'] : true;
            $expectedMaterialId = isset($case['expected_material_id']) ? (int) $case['expected_material_id'] : null;

            // Construct student context
            $student = new User([
                'institute_id' => $instituteId,
                'current_grade' => $grade,
                'medium' => $medium,
                'role' => 'user',
            ]);

            $result = $retrievalService->retrieve($student, $question, $subject);
            $chunks = $result['chunks'];
            $topScore = !empty($chunks) ? round($chunks[0]['similarity'], 4) : 0.0;
            $retrievedMaterialIds = array_values(array_unique(array_column($chunks, 'material_id')));

            $hit = false;
            if ($shouldAnswer) {
                $answerableTotal++;
                $answerableScores[] = $topScore;
                if ($expectedMaterialId !== null && in_array($expectedMaterialId, $retrievedMaterialIds, true)) {
                    $answerableHits++;
                    $hit = true;
                }
            } else {
                $negativeScores[] = $topScore;
            }

            $evalRecords[] = [
                'should_answer' => $shouldAnswer,
                'top_score' => $topScore,
                'hit' => $hit,
            ];

            $questionSnippet = mb_strlen($question) > 35 ? mb_substr($question, 0, 32) . '...' : $question;
            $retrievedList = implode(', ', $retrievedMaterialIds) ?: 'None';

            $tableRows[] = [
                $index + 1,
                $questionSnippet,
                $result['language'],
                $grade,
                $shouldAnswer ? 'YES' : '<comment>NO (neg)</comment>',
                $expectedMaterialId ?? 'N/A',
                $retrievedList,
                number_format($topScore, 4),
                $shouldAnswer ? ($hit ? '<info>HIT</info>' : '<error>MISS</error>') : '-',
            ];
        }

        $this->table(
            ['#', 'Question', 'Lang', 'Grade', 'Should Ans?', 'Exp ID', 'Retrieved IDs', 'Top Score', 'Result'],
            $tableRows
        );

        // 1. Answerable Questions Summary
        $this->newLine();
        $this->info("=================== ANSWERABLE QUESTIONS METRICS ===================");
        $hitRate = $answerableTotal > 0 ? round(($answerableHits / $answerableTotal) * 100, 2) : 0.0;
        $ansAvg = !empty($answerableScores) ? round(array_sum($answerableScores) / count($answerableScores), 4) : 0.0;
        $ansMin = !empty($answerableScores) ? min($answerableScores) : 0.0;
        $ansMax = !empty($answerableScores) ? max($answerableScores) : 0.0;

        $this->line("Answerable Questions: {$answerableTotal}");
        $this->line("Expected Material Retrieved (Hit Rate): {$answerableHits}/{$answerableTotal} ({$hitRate}%)");
        $this->line("Top Score Distribution: Min = {$ansMin} | Avg = {$ansAvg} | Max = {$ansMax}");

        // 2. Negative Questions Summary
        $negTotal = count($negativeScores);
        $this->newLine();
        $this->info("=================== NEGATIVE QUESTIONS (NO NOTES) METRICS ===================");
        $negAvg = !empty($negativeScores) ? round(array_sum($negativeScores) / count($negativeScores), 4) : 0.0;
        $negMin = !empty($negativeScores) ? min($negativeScores) : 0.0;
        $negMax = !empty($negativeScores) ? max($negativeScores) : 0.0;

        $this->line("Negative Questions: {$negTotal}");
        $this->line("Top Score Distribution: Min = {$negMin} | Avg = {$negAvg} | Max = {$negMax}");

        // 3. Threshold Calibration Sweep Table (0.50 to 0.90 with step 0.05)
        $this->newLine();
        $this->info("=================== THRESHOLD CALIBRATION SWEEP (0.50 - 0.90) ===================");
        $sweepRows = [];

        for ($t = 0.50; $t <= 0.905; $t += 0.05) {
            $threshold = round($t, 2);
            $falseNegatives = 0; // Answerable question rejected (score < threshold)
            $falsePositives = 0; // Negative question answered (score >= threshold)

            foreach ($evalRecords as $rec) {
                if ($rec['should_answer']) {
                    if ($rec['top_score'] < $threshold) {
                        $falseNegatives++;
                    }
                } else {
                    if ($rec['top_score'] >= $threshold) {
                        $falsePositives++;
                    }
                }
            }

            $totalErrors = $falseNegatives + $falsePositives;
            $totalQuestions = count($evalRecords);
            $errorRate = $totalQuestions > 0 ? round(($totalErrors / $totalQuestions) * 100, 1) : 0.0;

            $currentMarker = abs($threshold - (float) config('rag.retrieval.similarity_threshold', 0.70)) < 0.01 ? ' <-- CURRENT' : '';

            $sweepRows[] = [
                number_format($threshold, 2) . $currentMarker,
                $falseNegatives . " / {$answerableTotal}",
                $falsePositives . " / {$negTotal}",
                $totalErrors,
                "{$errorRate}%",
            ];
        }

        $this->table(
            ['Threshold', 'False Negatives (Wrongly Rejected)', 'False Positives (Wrongly Answered)', 'Total Errors', 'Error Rate'],
            $sweepRows
        );

        $this->info("==================================================================================");

        return self::SUCCESS;
    }
}
