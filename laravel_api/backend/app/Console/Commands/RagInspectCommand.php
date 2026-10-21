<?php

namespace App\Console\Commands;

use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Services\Rag\TextReadabilityChecker;
use Illuminate\Console\Command;

class RagInspectCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:inspect {material_id : Learning material ID to inspect}';

    /**
     * The console command description.
     */
    protected $description = 'Inspect learning material indexing status, page count, chunk count, pages using OCR, readability score, and share of Tamil letters';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $materialId = (int) $this->argument('material_id');

        $material = LearningMaterial::withoutGlobalScopes()->find($materialId);
        if (!$material) {
            $this->error("Learning material #{$materialId} not found.");
            return self::FAILURE;
        }

        $chunks = MaterialChunk::withoutGlobalScopes()
            ->where('material_id', $material->id)
            ->orderBy('page_number')
            ->orderBy('id')
            ->get();

        $pageCount = $chunks->max('page_number') ?? 0;
        $chunkCount = $chunks->count();

        $ocrPages = $chunks->where('is_ocr', true)
            ->pluck('page_number')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $ocrPagesSummary = empty($ocrPages)
            ? 'None (Direct extraction)'
            : implode(', ', $ocrPages) . ' (' . count($ocrPages) . ' page(s))';

        $statusFormatted = $material->rag_status ?? 'pending';

        // Calculate readability score and Tamil script share
        $allText = $chunks->pluck('content')->implode("\n\n");
        $totalChars = mb_strlen($allText, 'UTF-8');
        $tamilMatches = preg_match_all('/[\x{0B80}-\x{0BFF}]/u', $allText);
        $tamilShare = ($totalChars > 0)
            ? round(($tamilMatches / $totalChars) * 100, 2) . '%'
            : '0%';

        $readabilityScore = 'N/A';
        if (!empty(trim($allText))) {
            try {
                $readabilityChecker = app(TextReadabilityChecker::class);
                $heuristic = $readabilityChecker->evaluateHeuristic($allText, $material->medium);
                $readabilityScore = number_format($heuristic['score'], 4);
            } catch (\Throwable $e) {
                $readabilityScore = 'N/A';
            }
        }

        $this->info("=== Learning Material #{$material->id} RAG Inspection ===");
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $material->id],
                ['Title', $material->title],
                ['Institute ID', $material->institute_id ?? 'N/A'],
                ['Grade', $material->grade ?? 'N/A'],
                ['Medium', $material->medium ?? 'N/A'],
                ['Subject', $material->subject ?? 'N/A'],
                ['File Path', $material->file_path ?? 'N/A'],
                ['RAG Status', $statusFormatted],
                ['RAG Error', (empty($material->rag_error) || str_starts_with((string) $material->rag_error, 'Warning:')) ? 'None' : $material->rag_error],
                ['OCR Warnings', str_starts_with((string) $material->rag_error, 'Warning:') ? "<comment>{$material->rag_error}</comment>" : 'None'],
                ['Indexed At', $material->rag_indexed_at ? \Illuminate\Support\Carbon::parse($material->rag_indexed_at)->toDateTimeString() : 'Never'],
                ['Total Pages', $pageCount],
                ['Chunk Count', $chunkCount],
                ['Pages Using OCR', $ocrPagesSummary],
                ['Detected Language', $material->detected_language ?? 'N/A'],
                ['Readability Score', $readabilityScore],
                ['Share of Tamil Letters', $tamilShare],
            ]
        );

        $mediumNorm = strtolower(trim((string) $material->medium));
        $detectedNorm = strtolower(trim((string) $material->detected_language));
        if (!empty($mediumNorm) && !empty($detectedNorm) && !in_array($mediumNorm, ['both', 'all'], true) && $mediumNorm !== $detectedNorm) {
            $this->warn("⚠️  Warning: Medium/script mismatch — configured medium is '{$material->medium}', but detected document language is '{$material->detected_language}'.");
        }

        $this->newLine();

        $this->info("=== First 3 Chunks in Full ===");
        if ($chunks->isEmpty()) {
            $this->comment("No chunks have been generated or indexed for this material.");
        } else {
            foreach ($chunks->take(3) as $i => $chunk) {
                $ocrLabel = $chunk->is_ocr ? '<error>YES (OCR Fallback)</error>' : '<info>NO (Direct Text)</info>';
                $this->line("<comment>--- Chunk #" . ($i + 1) . " (Page: {$chunk->page_number}, Words: {$chunk->word_count}, OCR: {$ocrLabel}) ---</comment>");
                $this->line($chunk->content);
                $this->newLine();
            }
        }

        return self::SUCCESS;
    }
}
