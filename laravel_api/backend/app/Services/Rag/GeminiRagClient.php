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
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.api_key') ?: env('GEMINI_API_KEY'));
        $this->embeddingModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
        $this->dimensions = (int) config('rag.models.embedding_dimensions', 768);
        $this->llmModel = (string) config('rag.models.llm', 'gemini-3.5-flash');
        $this->timeout = (int) config('rag.ingestion.gemini_timeout', 45);
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
    public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->llmModel}:generateContent?key={$this->apiKey}";

        $prompt = "You are an OCR extraction engine. Extract all text from this scanned document page verbatim. " .
            "Preserve original Tamil (தமிழ்), Sinhala (සිංහල), and English text, numbers, formulas, and layout accurately. " .
            "Output ONLY the extracted text. Do not add markdown commentary, introductory greetings, or notes.";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => base64_encode($binaryData),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::timeout($this->timeout)->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Gemini Vision OCR API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("Gemini OCR request failed ({$response->status()}): " . $response->body());
        }

        $data = $response->json();
        return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
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
        $response = Http::timeout($timeout)->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Gemini GenerateContent API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("Gemini generation failed ({$response->status()}): " . $response->body());
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
}
