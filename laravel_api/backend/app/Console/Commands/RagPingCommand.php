<?php

namespace App\Console\Commands;

use App\Contracts\Rag\GeminiClientInterface;
use Illuminate\Console\Command;
use Throwable;

class RagPingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:ping';

    /**
     * The console command description.
     */
    protected $description = 'Health check: perform a short embedding and LLM call to verify Gemini API credentials';

    /**
     * Execute the console command.
     */
    public function handle(GeminiClientInterface $client): int
    {
        $embeddingModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
        $llmModel = (string) config('rag.models.llm', 'gemini-3.5-flash');

        $this->info("=== RAG Health Ping ===");
        $this->line(" • Embedding Model: {$embeddingModel}");
        $this->line(" • LLM Model:       {$llmModel}\n");

        // 1. Embedding Ping
        $this->output->write("1. Testing Gemini Embedding API... ");
        try {
            $vector = $client->embedContent('ping', 'RETRIEVAL_QUERY');
            if (empty($vector) || !is_array($vector)) {
                $this->output->writeln("<error>FAIL (Empty embedding vector returned)</error>");
                return self::FAILURE;
            }
            $dim = count($vector);
            $this->output->writeln("<info>OK ({$dim} dimensions)</info>");
        } catch (Throwable $e) {
            $safeError = $this->sanitizeError($e->getMessage());
            $this->output->writeln("<error>FAIL: {$safeError}</error>");
            return self::FAILURE;
        }

        // 2. LLM Ping
        $this->output->write("2. Testing Gemini LLM Generation API... ");
        try {
            $answer = $client->generateAnswer('Reply with PONG', 'Be concise. Reply with one word: PONG.');
            $trimmed = trim($answer);
            if (empty($trimmed)) {
                $this->output->writeln("<error>FAIL (Empty response from LLM)</error>");
                return self::FAILURE;
            }
            $this->output->writeln("<info>OK (response: {$trimmed})</info>");
        } catch (Throwable $e) {
            $safeError = $this->sanitizeError($e->getMessage());
            $this->output->writeln("<error>FAIL: {$safeError}</error>");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("RAG Ping: OK - All Gemini endpoints healthy.");
        return self::SUCCESS;
    }

    /**
     * Sanitize error message to ensure API key is NEVER printed.
     */
    protected function sanitizeError(string $message): string
    {
        $sanitized = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $message);
        return mb_substr(strip_tags($sanitized), 0, 300);
    }
}
