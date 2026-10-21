<?php

namespace App\Services\Rag;

use App\Contracts\Rag\GeminiClientInterface;
use Illuminate\Support\Facades\Log;

class TextReadabilityChecker
{
    protected GeminiClientInterface $geminiClient;
    protected float $minScore;
    protected int $sampleWords;

    public function __construct(GeminiClientInterface $geminiClient)
    {
        $this->geminiClient = $geminiClient;
        $this->minScore = (float) config('rag.ingestion.readability_min_score', 0.60);
        $this->sampleWords = (int) config('rag.ingestion.readability_sample_words', 150);
    }

    /**
     * Check whether extracted text is coherent, readable natural human language.
     *
     * @param array<int, array{page_number: int|null, text: string}> $pages
     * @param string|null $medium
     * @return array{readable: bool, score: float, language: string, reason: string}
     */
    public function check(array $pages, ?string $medium = null): array
    {
        $sample = $this->extractSample($pages);

        if (empty(trim($sample))) {
            return [
                'readable' => false,
                'score' => 0.0,
                'language' => $medium ?: 'unknown',
                'reason' => 'Extracted text is empty or has no readable characters.',
            ];
        }

        $wordParts = preg_split('/\s+/u', trim($sample), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($wordParts) < 10) {
            return [
                'readable' => false,
                'score' => 0.0,
                'language' => $medium ?: 'unknown',
                'reason' => 'Extracted text contains fewer than 10 words.',
            ];
        }

        $heuristic = $this->evaluateHeuristic($sample, $medium);

        // Fail-closed rule: a heuristic may REJECT text, but must NEVER be the only thing that APPROVES it.
        if (!$heuristic['readable']) {
            Log::info('TextReadabilityChecker: Text rejected by heuristic pre-check.', [
                'score' => $heuristic['score'],
                'reason' => $heuristic['reason'],
            ]);
            return $heuristic;
        }

        $systemInstruction = "You are an automated document text quality analyzer for Sri Lankan school educational materials. " .
            "Evaluate whether the sample text contains coherent, readable, grammatically recognizable human language (in Tamil, Sinhala, English, or mixed), " .
            "or if it is garbled, corrupted nonsense, bad OCR output, or broken font encoding. " .
            "Output JSON only with keys: readable (bool), score (float 0.0 to 1.0), language (string), reason (string).";

        $prompt = "Analyze the readability of this educational document sample:\n\n" .
            "Return ONLY a strict JSON object with this exact structure:\n" .
            "{\n" .
            "  \"readable\": true,\n" .
            "  \"score\": 0.95,\n" .
            "  \"language\": \"tamil\",\n" .
            "  \"reason\": \"Coherent Tamil text with normal sentence structure\"\n" .
            "}\n\n" .
            "Text sample to evaluate:\n" .
            $sample;

        try {
            $response = $this->geminiClient->generateAnswer($prompt, $systemInstruction);

            // Strip markdown code fences if present
            $cleaned = trim($response);
            if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $cleaned, $m)) {
                $cleaned = $m[1];
            }

            $data = json_decode($cleaned, true);
            if (!is_array($data) || !isset($data['readable'])) {
                if (preg_match('/\{[^{}]*"readable"[^{}]*\}/s', $cleaned, $m2)) {
                    $data = json_decode($m2[0], true);
                }
            }

            if (!is_array($data) || !isset($data['readable'])) {
                Log::warning('TextReadabilityChecker: LLM did not return valid JSON, quality check unavailable.', [
                    'raw_response' => mb_substr($response, 0, 200),
                ]);
                throw new QualityCheckUnavailableException('quality check unavailable, retry later');
            }

            $readable = (bool) ($data['readable'] ?? false);
            $score = (float) ($data['score'] ?? 0.0);
            $language = strtolower((string) ($data['language'] ?? ($medium ?: 'unknown')));
            $reason = (string) ($data['reason'] ?? 'Quality evaluation completed.');

            $isAcceptable = $readable && ($score >= $this->minScore);

            Log::info('TextReadabilityChecker: Quality check completed.', [
                'readable' => $isAcceptable,
                'score' => $score,
                'language' => $language,
                'reason' => $reason,
            ]);

            return [
                'readable' => $isAcceptable,
                'score' => $score,
                'language' => $language,
                'reason' => $reason,
            ];

        } catch (QualityCheckUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('TextReadabilityChecker: Exception during LLM evaluation, quality check unavailable: ' . $e->getMessage());
            throw new QualityCheckUnavailableException('quality check unavailable, retry later', 0, $e);
        }
    }

    /**
     * Compute fast readability score using Unicode character distributions, mojibake, and question mark rates.
     *
     * @param string $sample
     * @param string|null $medium
     * @return array{readable: bool, score: float, language: string, reason: string}
     */
    public function evaluateHeuristic(string $sample, ?string $medium = null): array
    {
        $len = mb_strlen($sample, 'UTF-8');
        if ($len < 10) {
            return [
                'readable' => false,
                'score' => 0.0,
                'language' => $medium ?: 'unknown',
                'reason' => 'Text sample is too short to evaluate.',
            ];
        }

        // Count Latin-1 mojibake glyphs (U+00C0-U+00FF, excluding math symbols)
        $mojibakeCount = preg_match_all('/[\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{00FF}]/u', $sample);
        // Count question marks (often from failed encoding or OCR scrub)
        $qMarkCount = substr_count($sample, '?');
        // Count valid clean characters: ASCII printable + Tamil + Sinhala + math symbols + whitespace
        $validCount = preg_match_all('/[\x{0020}-\x{007E}\x{0B80}-\x{0BFF}\x{0D80}-\x{0DFF}\x{00B0}\x{00B1}\x{00B2}\x{00B3}\x{00B5}\x{00D7}\x{00F7}\p{Sm}\r\n\t]/u', $sample);

        $validRatio = $validCount / $len;
        $mojibakeRatio = $mojibakeCount / $len;
        $qRatio = $qMarkCount / $len;

        // Base quality score
        $score = max(0.0, min(1.0, $validRatio - ($mojibakeRatio * 2) - ($qRatio * 1.5)));

        $langDetector = app(LanguageDetector::class);
        $langCode = $langDetector->detect($sample);
        $langName = $langDetector->getLanguageName($langCode);

        $readable = ($score >= $this->minScore);

        return [
            'readable' => $readable,
            'score' => round($score, 4),
            'language' => strtolower($langName),
            'reason' => $readable
                ? "Heuristic check passed (score: " . round($score, 2) . ", lang: {$langName})."
                : "Heuristic check failed: text quality below threshold (score: " . round($score, 2) . ").",
        ];
    }

    /**
     * Extract a small sample: a few hundred words across up to 3 places in the document.
     */
    protected function extractSample(array $pages): string
    {
        if (empty($pages)) {
            return '';
        }

        $allTexts = [];
        foreach ($pages as $p) {
            $t = trim($p['text'] ?? '');
            if (!empty($t)) {
                $allTexts[] = $t;
            }
        }

        if (empty($allTexts)) {
            return '';
        }

        $numTexts = count($allTexts);

        if ($numTexts === 1) {
            $words = preg_split('/\s+/u', $allTexts[0], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $totalW = count($words);
            if ($totalW <= $this->sampleWords * 3) {
                return implode(' ', $words);
            }
            $s1 = implode(' ', array_slice($words, 0, $this->sampleWords));
            $s2 = implode(' ', array_slice($words, (int) floor($totalW / 2), $this->sampleWords));
            $s3 = implode(' ', array_slice($words, max(0, $totalW - $this->sampleWords), $this->sampleWords));
            return $s1 . "\n\n" . $s2 . "\n\n" . $s3;
        }

        $indices = ($numTexts === 2)
            ? [0, 1]
            : [0, (int) floor($numTexts / 2), $numTexts - 1];

        $sampleParts = [];
        foreach ($indices as $idx) {
            $words = preg_split('/\s+/u', $allTexts[$idx], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $slice = array_slice($words, 0, $this->sampleWords);
            if (!empty($slice)) {
                $sampleParts[] = implode(' ', $slice);
            }
        }

        return implode("\n\n---\n\n", $sampleParts);
    }
}
