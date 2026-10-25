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
    protected $signature = 'rag:inspect {material_id? : Learning material ID to inspect} {--chunk= : Specific chunk ID to inspect} {--all : Print all chunks}';

    /**
     * The console command description.
     */
    protected $description = 'Inspect learning material indexing status, page count, chunk count, pages using OCR, readability score, and chunk text';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificChunkId = $this->option('chunk');

        if (!empty($specificChunkId)) {
            $chunk = MaterialChunk::withoutGlobalScopes()->with('material')->find((int) $specificChunkId);
            if (!$chunk) {
                $this->error("Material chunk #{$specificChunkId} not found.");
                return self::FAILURE;
            }

            $ocrLabel = $chunk->is_ocr ? 'YES (OCR Fallback)' : 'NO (Direct Text)';
            $this->info("=== Chunk #{$chunk->id} Inspection ===");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Chunk ID', $chunk->id],
                    ['Material ID', $chunk->material_id],
                    ['Material Title', $chunk->material?->title ?? 'N/A'],
                    ['Grade', $chunk->grade ?? 'N/A'],
                    ['Medium', $chunk->medium ?? 'N/A'],
                    ['Subject', $chunk->subject ?? 'N/A'],
                    ['Page Number', $chunk->page_number ?? 'N/A'],
                    ['Question Range', $chunk->question_number ?? 'None'],
                    ['Word Count', $chunk->word_count ?? 0],
                    ['Is OCR', $ocrLabel],
                    ['Embedding Model', $chunk->embedding_model ?? 'N/A'],
                    ['Dimensions', $chunk->dimensions ?? 'N/A'],
                ]
            );

            $this->newLine();
            $this->info("=== Chunk #{$chunk->id} Full Text ===");
            $this->line($chunk->content);
            return self::SUCCESS;
        }

        $materialId = (int) $this->argument('material_id');
        if (empty($materialId)) {
            $this->error("Please provide either a material_id or --chunk=<id>.");
            return self::FAILURE;
        }

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

        $this->newLine();

        $chunksToShow = $this->option('all') ? $chunks : $chunks->take(3);
        $header = $this->option('all') ? "=== All Chunks in Full ({$chunkCount} chunks) ===" : "=== First 3 Chunks in Full ===";
        $this->info($header);

        if ($chunks->isEmpty()) {
            $this->comment("No chunks have been generated or indexed for this material.");
        } else {
            foreach ($chunksToShow as $i => $chunk) {
                $ocrLabel = $chunk->is_ocr ? 'YES (OCR Fallback)' : 'NO (Direct Text)';
                $this->line("<comment>--- Chunk #{$chunk->id} (Page: {$chunk->page_number}, Questions: {$chunk->question_number}, Words: {$chunk->word_count}, OCR: {$ocrLabel}) ---</comment>");
                $this->line($chunk->content);
                $this->newLine();
            }
        }

        return self::SUCCESS;
    }
}