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
}
