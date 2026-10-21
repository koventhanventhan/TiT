<?php

namespace App\Services\Rag;

use App\Contracts\Rag\GeminiClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiRagClient implements GeminiClientInterface
{
    protected string $apiKey;
    protected string $embeddingModel;
    protected int $dimensions;
    protected string $llmModel;
    protected string $ocrModel;
    protected ?string $ocrFallbackModel;
    protected int $timeout;
    protected float $ocrTemperature;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.api_key') ?: env('GEMINI_API_KEY'));
        $this->embeddingModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
        $this->dimensions = (int) config('rag.models.embedding_dimensions', 768);
        $this->llmModel = (string) config('rag.models.llm', 'gemini-3.5-flash');
        $this->ocrModel = (string) (config('rag.models.ocr') ?: config('rag.models.llm', 'gemini-3.5-flash'));
        $this->ocrFallbackModel = config('rag.models.ocr_fallback') ? (string) config('rag.models.ocr_fallback') : null;
        $this->timeout = (int) config('rag.ingestion.gemini_timeout', 45);
        $this->ocrTemperature = (float) config('rag.ocr.temperature', 0.0);
    }

    /**
     * Generate embedding vector using Gemini Embedding API.
     *
     * @param string $text
     * @param string $taskType 'RETRIEVAL_DOCUMENT' or 'RETRIEVAL_QUERY'
     * @param string|null $title Optional document title for retrieval context
     * @return array<int, float>
     */
    /**
     * Generate embedding vector using Gemini Embedding API.
     */
    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        $results = $this->batchEmbedContents([$text], $taskType, $title);
        return $results[0] ?? [];
    }

    /**
     * Generate embedding vectors for multiple texts in batches with exponential backoff on 429/5xx.
     *
     * @param array<int, string> $texts
     * @param string $taskType
     * @param string|null $title
     * @return array<int, array<int, float>>
     */
    public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        if (empty($texts)) {
            return [];
        }

        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $batchSize = (int) config('rag.chunking.embedding_batch_size', 25);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->embeddingModel}:batchEmbedContents?key={$this->apiKey}";
        $textChunks = array_chunk($texts, $batchSize);
        $allEmbeddings = [];

        foreach ($textChunks as $chunk) {
            $requests = [];
            foreach ($chunk as $text) {
                $req = [
                    'model' => 'models/' . $this->embeddingModel,
                    'content' => [
                        'parts' => [
                            ['text' => $text],
                        ],
                    ],
                    'taskType' => $taskType,
                    'outputDimensionality' => $this->dimensions,
                ];

                if (!empty($title) && $taskType === 'RETRIEVAL_DOCUMENT') {
                    $req['title'] = $title;
                }

                $requests[] = $req;
            }

            // Exponential backoff retry loop on 429 or 5xx
            $maxAttempts = 3;
            $delayMs = 1000;
            $lastException = null;
            $batchSuccess = false;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::timeout($this->timeout)->post($url, [
                        'requests' => $requests,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $batchEmbeddings = $data['embeddings'] ?? [];

                        if (count($batchEmbeddings) !== count($requests)) {
                            throw new RuntimeException('Gemini batchEmbedContents returned mismatched count of embeddings.');
                        }

                        foreach ($batchEmbeddings as $emb) {
                            $allEmbeddings[] = $emb['values'] ?? [];
                        }

                        $batchSuccess = true;
                        break;
                    }

                    $status = $response->status();
                    if ($attempt === $maxAttempts || !in_array($status, [429, 500, 502, 503, 504])) {
                        Log::error("Gemini batchEmbedContents failed permanently with status {$status}", [
                            'body' => $response->body(),
                        ]);
                        throw new RuntimeException("Gemini batchEmbedContents failed ({$status}): " . $response->body());
                    }

                    Log::warning("Gemini batchEmbedContents throttled/errored (status {$status}), retrying attempt {$attempt}/{$maxAttempts} in {$delayMs}ms...");
                    usleep($delayMs * 1000);
                    $delayMs *= 2;

                } catch (\Throwable $e) {
                    $lastException = $e;
                    if ($attempt === $maxAttempts) {
                        throw $e;
                    }
                    usleep($delayMs * 1000);
                    $delayMs *= 2;
                }
            }

            if (!$batchSuccess && $lastException) {
                throw $lastException;
            }
        }

        return $allEmbeddings;
    }

    /**
     * Extract text from a page image using Gemini Vision OCR (supports Tamil, Sinhala, English).
     *
     * @param string $binaryData Raw image bytes
     * @param string $mimeType
     * @return string Extracted text verbatim
     */
        /**
     * Downscale high-resolution images to fit within max dimensions and target JPEG quality.
     */
    public function downscaleImage(string $binaryData, int $maxDimension = 1600, int $quality = 80): string
    {
        if (!function_exists('imagecreatefromstring') || strlen($binaryData) < 1000) {
            return $binaryData;
        }

        $img = @imagecreatefromstring($binaryData);
        if (!$img) {
            return $binaryData;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        if ($w <= $maxDimension && $h <= $maxDimension) {
            imagedestroy($img);
            return $binaryData;
        }

        $scale = min($maxDimension / $w, $maxDimension / $h);
        $newW = (int) round($w * $scale);
        $newH = (int) round($h * $scale);

        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);

        ob_start();
        imagejpeg($resized, null, $quality);
        $downscaled = ob_get_clean();

        imagedestroy($img);
        imagedestroy($resized);

        return $downscaled ?: $binaryData;
    }

    public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $maxDim = (int) config('rag.ingestion.ocr_max_dimension', 1600);
        $jpegQuality = (int) config('rag.ingestion.ocr_jpeg_quality', 80);
        $binaryData = $this->downscaleImage($binaryData, $maxDim, $jpegQuality);

        $prompt = 'You are a strict OCR transcription engine. Transcribe all text from this scanned document page exactly and verbatim without correcting spelling, translating, or summarising. Preserve all characters, layout, Tamil (தமிழ்), Sinhala (සිංහල), and English text, formulas, punctuation, and keep all numbers and dates exactly as printed. Output ONLY the extracted text. Do not add markdown commentary, introductory greetings, explanations, or notes.';

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode($binaryData),
                            ],
                        ],
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $this->ocrTemperature,
            ],
        ];

        $models = [$this->ocrModel];
        if (!empty($this->ocrFallbackModel) && $this->ocrFallbackModel !== $this->ocrModel) {
            $models[] = $this->ocrFallbackModel;
        }

        $backoffMs = app()->runningUnitTests() ? [0, 0, 0] : [2000, 5000, 10000];
        $maxAttemptsPerModel = count($backoffMs) + 1;
        $lastException = null;
        $lastStatus = null;
        $lastGoogleStatus = null;
        $lastShortMessage = null;

        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            for ($attempt = 1; $attempt <= $maxAttemptsPerModel; $attempt++) {
                try {
                    $response = Http::withHeaders([
                        'x-goog-api-key' => $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])->withOptions([
                        'connect_timeout' => 30,
                    ])->timeout($this->timeout)->post($url, $payload);

                    if ($response->successful()) {
                        $data = $response->json();
                        return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                    }

                    $status = $response->status();
                    $lastStatus = $status;
                    $errData = $response->json() ?? [];
                    $lastGoogleStatus = $errData['error']['status'] ?? null;
                    $lastShortMessage = $errData['error']['message'] ?? null;

                    Log::warning("Gemini Vision OCR attempt {$attempt}/{$maxAttemptsPerModel} with model '{$model}' returned status {$status}");

                    if (in_array($status, [429, 500, 502, 503, 504])) {
                        if ($attempt < $maxAttemptsPerModel) {
                            $delay = $backoffMs[$attempt - 1] ?? 2000;
                            if ($delay > 0) {
                                usleep($delay * 1000);
                            }
                            continue;
                        }
                    } else {
                        break;
                    }

                } catch (\Throwable $e) {
                    $lastException = $e;
                    $cleanMsg = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $e->getMessage());
                    Log::warning("Gemini Vision OCR exception attempt {$attempt}/{$maxAttemptsPerModel} on model '{$model}': {$cleanMsg}");

                    if ($attempt < $maxAttemptsPerModel) {
                        $delay = $backoffMs[$attempt - 1] ?? 2000;
                        if ($delay > 0) {
                            usleep($delay * 1000);
                        }
                    }
                }
            }
        }

        if ($lastStatus === 429 || in_array($lastGoogleStatus, ['RESOURCE_EXHAUSTED', 'UNAVAILABLE'])) {
            $quotaType = self::classifyQuotaError($lastShortMessage ?? '');
            throw new OcrUnavailableException(
                "Gemini OCR quota temporarily exhausted across all configured models.",
                $lastStatus ?? 429,
                $lastGoogleStatus ?? 'RESOURCE_EXHAUSTED',
                $lastShortMessage ?? $quotaType['label']
            );
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new RuntimeException("Gemini OCR failed for model [{$this->ocrModel}] after all retry attempts.");
    }

    /**
     * Generate grounded answer using Gemini LLM.
     *
     * @param string $prompt
     * @param string|null $systemInstruction
     * @return string
     */
    public function generateAnswer(string $prompt, ?string $systemInstruction = null): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->llmModel}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => (float) config('rag.llm.temperature', 0.2),
                'topP' => (float) config('rag.llm.top_p', 0.8),
                'maxOutputTokens' => (int) config('rag.llm.max_output_tokens', 1500),
            ],
        ];

        $safetySettings = config('rag.llm.safety_settings', []);
        if (!empty($safetySettings)) {
            $payload['safetySettings'] = $safetySettings;
        }

        if (!empty($systemInstruction)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $timeout = (int) config('rag.llm.request_timeout', $this->timeout);
        $maxAttempts = (int) config('rag.llm.retry_429_max_attempts', 4);
        $totalWaitLimit = (int) config('rag.llm.retry_429_max_wait', 60);
        $totalWaitedSeconds = 0.0;
        $response = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout($timeout)->post($url, $payload);
                if ($response->successful()) {
                    break;
                }

                $status = $response->status();

                if (in_array($status, [429, 500, 502, 503, 504]) && $attempt < $maxAttempts) {
                    $waitSec = 0.0;

                    if ($status === 429) {
                        // 1. Check Retry-After HTTP header
                        $retryAfter = $response->header('Retry-After');
                        if (!empty($retryAfter)) {
                            if (is_numeric($retryAfter)) {
                                $waitSec = (float) $retryAfter;
                            } else {
                                $parsedTime = strtotime($retryAfter);
                                if ($parsedTime !== false) {
                                    $waitSec = max(0.0, (float) ($parsedTime - time()));
                                }
                            }
                        }

                        // 2. Check response body RetryInfo if header was absent or 0
                        if ($waitSec <= 0.0) {
                            $bodyJson = $response->json();
                            $details = $bodyJson['error']['details'] ?? [];
                            foreach ($details as $detail) {
                                if (($detail['@type'] ?? '') === 'type.googleapis.com/google.rpc.RetryInfo' && !empty($detail['retryDelay'])) {
                                    if (preg_match('/^(\d+(?:\.\d+)?)s?$/i', (string) $detail['retryDelay'], $m)) {
                                        $waitSec = (float) $m[1];
                                        break;
                                    }
                                }
                            }
                        }

                        // 3. Fallback exponential backoff with jitter
                        if ($waitSec <= 0.0) {
                            $waitSec = min(pow(2, $attempt - 1) * 1.5, 16.0) + (mt_rand(100, 500) / 1000.0);
                        }

                        // 4. Enforce total wait limit from config
                        if (($totalWaitedSeconds + $waitSec) > $totalWaitLimit) {
                            Log::warning("Gemini 429 retry aborted: next wait {$waitSec}s exceeds total wait limit ({$totalWaitLimit}s). Total waited: {$totalWaitedSeconds}s.");
                            break;
                        }

                        Log::warning("Gemini 429 rate limit hit on attempt {$attempt}/{$maxAttempts}, waiting {$waitSec}s (total waited: {$totalWaitedSeconds}s, limit: {$totalWaitLimit}s)...");
                    } else {
                        // 5xx server errors
                        $waitSec = min(pow(2, $attempt - 1) * 1.5, 8.0) + (mt_rand(100, 500) / 1000.0);
                        if (($totalWaitedSeconds + $waitSec) > $totalWaitLimit) {
                            break;
                        }
                    }

                    if ($waitSec > 0) {
                        usleep((int) ($waitSec * 1000000));
                        $totalWaitedSeconds += $waitSec;
                    }
                    continue;
                }
            } catch (\Throwable $e) {
                if ($attempt < $maxAttempts && $totalWaitedSeconds < $totalWaitLimit) {
                    $waitSec = min(pow(2, $attempt - 1) * 1.5, 8.0);
                    if (($totalWaitedSeconds + $waitSec) <= $totalWaitLimit) {
                        usleep((int) ($waitSec * 1000000));
                        $totalWaitedSeconds += $waitSec;
                        continue;
                    }
                }
                throw $e;
            }
        }

        if (!$response || !$response->successful()) {
            $status = $response ? $response->status() : 500;
            $body = $response ? $response->body() : 'No response';
            Log::error('Gemini GenerateContent API Error', [
                'status' => $status,
                'body' => $body,
            ]);
            throw new RuntimeException("Gemini generation failed ({$status}): " . $body);
        }

        $data = $response->json();

        // Check for prompt-level safety block
        if (!empty($data['promptFeedback']['blockReason'])) {
            $reason = (string) $data['promptFeedback']['blockReason'];
            $ratings = $data['promptFeedback']['safetyRatings'] ?? [];
            throw new GeminiSafetyBlockedException("Gemini prompt blocked ({$reason})", $reason, null, $ratings);
        }

        // Check for candidate-level safety block
        $candidate = $data['candidates'][0] ?? null;
        if ($candidate) {
            $finishReason = $candidate['finishReason'] ?? null;
            if ($finishReason === 'SAFETY') {
                $ratings = $candidate['safetyRatings'] ?? [];
                throw new GeminiSafetyBlockedException("Gemini generation blocked by safety filters.", null, 'SAFETY', $ratings);
            }
        }

        // Log token usage per query
        if (!empty($data['usageMetadata'])) {
            $usage = $data['usageMetadata'];
            Log::info('Gemini LLM Token Usage (generateAnswer)', [
                'prompt_token_count' => $usage['promptTokenCount'] ?? null,
                'candidates_token_count' => $usage['candidatesTokenCount'] ?? null,
                'total_token_count' => $usage['totalTokenCount'] ?? null,
            ]);
        }

        return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    /**
     * Stream grounded answer using Gemini streamGenerateContent SSE API.
     *
     * @param string $prompt
     * @param string|null $systemInstruction
     * @param callable(string): void $onChunk
     * @return void
     */
    public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->llmModel}:streamGenerateContent?alt=sse&key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => (float) config('rag.llm.temperature', 0.2),
                'topP' => (float) config('rag.llm.top_p', 0.8),
                'maxOutputTokens' => (int) config('rag.llm.max_output_tokens', 1500),
            ],
        ];

        $safetySettings = config('rag.llm.safety_settings', []);
        if (!empty($safetySettings)) {
            $payload['safetySettings'] = $safetySettings;
        }

        if (!empty($systemInstruction)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $timeout = (int) config('rag.llm.request_timeout', $this->timeout);
        $response = Http::withOptions(['stream' => true])
            ->timeout($timeout)
            ->post($url, $payload);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            Log::error("Gemini streamGenerateContent Error ({$status}): {$body}");
            throw new RuntimeException("Gemini streaming failed ({$status}): {$body}");
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $lastUsage = null;

        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $trimmed = trim($line);

                if (str_starts_with($trimmed, 'data: ')) {
                    $jsonStr = substr($trimmed, 6);
                    if ($jsonStr === '[DONE]') {
                        break;
                    }

                    $data = json_decode($jsonStr, true);
                    if (is_array($data)) {
                        // Check prompt-level safety block
                        if (!empty($data['promptFeedback']['blockReason'])) {
                            $reason = (string) $data['promptFeedback']['blockReason'];
                            $ratings = $data['promptFeedback']['safetyRatings'] ?? [];
                            throw new GeminiSafetyBlockedException("Gemini prompt blocked ({$reason})", $reason, null, $ratings);
                        }

                        // Check candidate-level safety block
                        $candidate = $data['candidates'][0] ?? null;
                        if ($candidate) {
                            $finishReason = $candidate['finishReason'] ?? null;
                            if ($finishReason === 'SAFETY') {
                                $ratings = $candidate['safetyRatings'] ?? [];
                                throw new GeminiSafetyBlockedException("Gemini generation blocked by safety filters.", null, 'SAFETY', $ratings);
                            }
                        }

                        if (!empty($data['usageMetadata'])) {
                            $lastUsage = $data['usageMetadata'];
                        }

                        $text = $candidate['content']['parts'][0]['text'] ?? '';
                        if ($text !== '') {
                            $onChunk($text);
                        }
                    }
                }
            }
        }

        if ($lastUsage) {
            Log::info('Gemini LLM Token Usage (streamAnswer)', [
                'prompt_token_count' => $lastUsage['promptTokenCount'] ?? null,
                'candidates_token_count' => $lastUsage['candidatesTokenCount'] ?? null,
                'total_token_count' => $lastUsage['totalTokenCount'] ?? null,
            ]);
        }
    }

    /**
     * Classify a 429 quota/rate limit error into:
     * - 'per_minute': Rate limit (per-minute RPM/TPM limit hit)
     * - 'daily_or_billing': Daily or billing quota limit hit (RPD or billing exhausted)
     * - 'unspecified': Unspecified 429 RESOURCE_EXHAUSTED
     *
     * @param string|null $bodyOrMessage
     * @return array{category: string, label: string, detail: string}
     */
    public static function classifyQuotaError(?string $bodyOrMessage): array
    {
        if (empty($bodyOrMessage)) {
            return [
                'category' => 'unspecified',
                'label' => 'Unspecified 429',
                'detail' => "Google's response body did not provide details to distinguish between a per-minute rate limit and a daily/billing quota.",
            ];
        }

        $searchCorpus = $bodyOrMessage;
        if (str_starts_with(trim($bodyOrMessage), '{')) {
            $parsedJson = json_decode($bodyOrMessage, true);
            if (is_array($parsedJson)) {
                $details = $parsedJson['error']['details'] ?? [];
                foreach ($details as $detail) {
                    if (isset($detail['metadata'])) {
                        $searchCorpus .= ' ' . json_encode($detail['metadata']);
                    }
                    if (isset($detail['violations'])) {
                        $searchCorpus .= ' ' . json_encode($detail['violations']);
                    }
                    if (isset($detail['reason'])) {
                        $searchCorpus .= ' ' . $detail['reason'];
                    }
                }
            }
        }

        $isPerMinute = (bool) preg_match('/(?:PerMinute|per[ _-]minute|requests per minute|tokens per minute|RPM|TPM|RATE_LIMIT_EXCEEDED)/i', $searchCorpus);
        $isDailyOrBilling = (bool) preg_match('/(?:PerDay|per[ _-]day|daily|requests per day|tokens per day|RPD|billing|credits|QUOTA_EXCEEDED)/i', $searchCorpus);

        if ($isPerMinute && !$isDailyOrBilling) {
            return [
                'category' => 'per_minute',
                'label' => 'Rate Limit (Per-Minute)',
                'detail' => 'Key hit short-term requests/tokens per minute limits (RPM/TPM). Wait ~1 minute before re-trying.',
            ];
        }

        if ($isDailyOrBilling) {
            return [
                'category' => 'daily_or_billing',
                'label' => 'Quota Limit (Daily / Billing)',
                'detail' => 'Key hit daily requests quota (RPD) or billing/credit limit. Quota resets at midnight PT or requires billing plan upgrade.',
            ];
        }

        return [
            'category' => 'unspecified',
            'label' => 'Unspecified 429 Quota Exhaustion',
            'detail' => "HTTP 429 RESOURCE_EXHAUSTED returned. Google's response body did not distinguish whether this is a per-minute burst rate limit or a daily/billing quota.",
        ];
    }
}

