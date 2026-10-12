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
    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->embeddingModel}:embedContent?key={$this->apiKey}";

        $payload = [
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
            $payload['title'] = $title;
        }

        $response = Http::timeout($this->timeout)->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Gemini Embedding API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException("Gemini embedding request failed ({$response->status()}): " . $response->body());
        }

        $data = $response->json();
        $values = $data['embedding']['values'] ?? [];

        if (empty($values) || !is_array($values)) {
            throw new RuntimeException('Invalid or empty embedding values received from Gemini API.');
        }

        return $values;
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
