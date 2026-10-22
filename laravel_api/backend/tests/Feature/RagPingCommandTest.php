<?php

namespace Tests\Feature;

use App\Contracts\Rag\GeminiClientInterface;
use Tests\TestCase;

class RagPingCommandTest extends TestCase
{
    public function test_rag_ping_succeeds_when_endpoints_are_healthy(): void
    {
        $fakeClient = new class implements GeminiClientInterface {
            public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
            {
                return array_fill(0, 768, 0.01);
            }

            public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
            {
                return [array_fill(0, 768, 0.01)];
            }

            public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string
            {
                return 'Mock text';
            }

            public function generateAnswer(string $prompt, ?string $systemInstruction = null): string
            {
                return 'PONG';
            }

            public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void
            {
                $onChunk('PONG');
            }
        };

        $this->app->instance(GeminiClientInterface::class, $fakeClient);

        $this->artisan('rag:ping')
            ->expectsOutputToContain('OK (768 dimensions)')
            ->expectsOutputToContain('OK (response: PONG)')
            ->expectsOutputToContain('RAG Ping: OK - All Gemini endpoints healthy.')
            ->assertExitCode(0);
    }

    public function test_rag_ping_fails_and_sanitizes_errors_without_exposing_api_key(): void
    {
        $fakeClient = new class implements GeminiClientInterface {
            public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
            {
                throw new \RuntimeException('Failed to connect to Google API: key=AIzaSySecretApiKey12345 in URL.');
            }

            public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
            {
                return [];
            }

            public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string
            {
                return '';
            }

            public function generateAnswer(string $prompt, ?string $systemInstruction = null): string
            {
                return '';
            }

            public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void {}
        };

        $this->app->instance(GeminiClientInterface::class, $fakeClient);

        $this->artisan('rag:ping')
            ->expectsOutputToContain('FAIL: Failed to connect to Google API: key=*** in URL.')
            ->doesntExpectOutputToContain('AIzaSySecretApiKey12345')
            ->assertExitCode(1);
    }
}
