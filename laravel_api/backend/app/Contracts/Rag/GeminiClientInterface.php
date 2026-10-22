<?php

namespace App\Contracts\Rag;

interface GeminiClientInterface
{
    /**
     * Generate embedding vector using Gemini Embedding API.
     *
     * @param string $text
     * @param string $taskType 'RETRIEVAL_DOCUMENT' or 'RETRIEVAL_QUERY'
     * @param string|null $title Optional document title for retrieval context
     * @return array<int, float>
     */
    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array;

    /**
     * Generate embedding vectors for multiple texts in batches with exponential backoff.
     *
     * @param array<int, string> $texts
     * @param string $taskType
     * @param string|null $title
     * @return array<int, array<int, float>>
     */
    public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array;

    /**
     * Extract text from a page image using Gemini Vision OCR (supports Tamil, Sinhala, English).
     *
     * @param string $binaryData Raw image bytes
     * @param string $mimeType
     * @return string Extracted text verbatim
     */
    public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string;

    /**
     * Generate grounded answer using Gemini LLM.
     *
     * @param string $prompt
     * @param string|null $systemInstruction
     * @return string
     */
    public function generateAnswer(string $prompt, ?string $systemInstruction = null): string;

    /**
     * Stream grounded answer using Gemini streamGenerateContent SSE API.
     *
     * @param string $prompt
     * @param string|null $systemInstruction
     * @param callable(string): void $onChunk
     * @return void
     */
    public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void;
}
