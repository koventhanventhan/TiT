<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Rag\RetrievalService;
use Illuminate\Console\Command;

class RagEvalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:eval {file : Path to the evaluation JSON file} {--institute=1 : Institute ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate RAG retrieval accuracy and similarity scores against benchmark questions';

    public function handle(RetrievalService $retrievalService): int
    {
        $filePath = $this->argument('file');
        $instituteId = (int) $this->option('institute');

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

        $this->info("Evaluating " . count($testCases) . " questions for Institute #{$instituteId}...");

        $tableRows = [];
        $total = count($testCases);
        $correct = 0;
        $scores = [];

        foreach ($testCases as $index => $case) {
            $question = (string) ($case['question'] ?? '');
            $grade = (string) ($case['grade'] ?? '');
            $medium = (string) ($case['medium'] ?? 'all');
            $subject = isset($case['subject']) ? (string) $case['subject'] : null;
            $expectedMaterialId = isset($case['expected_material_id']) ? (int) $case['expected_material_id'] : null;

            // Construct mock student context
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

            $matched = false;
            if ($expectedMaterialId !== null) {
                $matched = in_array($expectedMaterialId, $retrievedMaterialIds, true);
                if ($matched) {
                    $correct++;
                }
            }

            if ($topScore > 0) {
                $scores[] = $topScore;
            }

            $questionSnippet = mb_strlen($question) > 40 ? mb_substr($question, 0, 37) . '...' : $question;
            $retrievedList = implode(', ', $retrievedMaterialIds) ?: 'None';

            $tableRows[] = [
                $index + 1,
                $questionSnippet,
                $result['language'],
                $grade,
                $medium,
                $expectedMaterialId ?? 'N/A',
                $retrievedList,
                number_format($topScore, 4),
                $matched ? '<info>YES</info>' : '<comment>NO</comment>',
            ];
        }

        $this->table(
            ['#', 'Question', 'Lang', 'Grade', 'Medium', 'Expected ID', 'Retrieved IDs', 'Top Score', 'Hit?'],
            $tableRows
        );

        // Summary report
        $accuracy = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;
        $avgScore = !empty($scores) ? round(array_sum($scores) / count($scores), 4) : 0.0;
        $minScore = !empty($scores) ? min($scores) : 0.0;
        $maxScore = !empty($scores) ? max($scores) : 0.0;

        $this->newLine();
        $this->info("=================== RAG EVALUATION METRICS ===================");
        $this->line("Total Test Questions: {$total}");
        $this->line("Expected Material Retrieved (Hit Rate): {$correct}/{$total} ({$accuracy}%)");
        $this->line("Average Top Similarity Score: {$avgScore}");
        $this->line("Min Top Score: {$minScore} | Max Top Score: {$maxScore}");
        $this->line("Current Configured Threshold: " . config('rag.retrieval.similarity_threshold', 0.70));
        $this->info("=============================================================");

        return self::SUCCESS;
    }
}
