<?php

namespace App\Jobs;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\LearningMaterial;
use App\Services\Rag\GeminiRagClient;
use App\Services\Rag\OcrUnavailableException;
use App\Services\Rag\PdfTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

class ProcessMaterialOcrPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $materialId;
    public int $pageNumber;
    public int $totalPages;
    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [5, 15, 30];

    public function __construct(int $materialId, int $pageNumber, int $totalPages = 1)
    {
        $this->materialId = $materialId;
        $this->pageNumber = $pageNumber;
        $this->totalPages = $totalPages;
    }

    /**
     * Execute the page OCR job.
     */
    public function handle(GeminiClientInterface $geminiClient, PdfTextExtractor $extractor): void
    {
        $tempPath = "rag_temp/material_{$this->materialId}/page_{$this->pageNumber}.json";

        // Idempotency check: If page already processed with non-empty text, skip
        if (Storage::disk('local')->exists($tempPath)) {
            $cached = json_decode(Storage::disk('local')->get($tempPath), true);
            if (!empty($cached['text'])) {
                Log::info("ProcessMaterialOcrPageJob: Page {$this->pageNumber} of material #{$this->materialId} already completed, skipping OCR.");
                return;
            }
        }

        $material = LearningMaterial::withoutGlobalScopes()->find($this->materialId);
        if (!$material) {
            Log::warning("ProcessMaterialOcrPageJob: Material #{$this->materialId} not found, aborting.");
            return;
        }

        // Reuse existing OCR result when the same file_hash was already OCR'd in the same institute
        if (!empty($material->file_hash) && !empty($material->institute_id)) {
            $existingChunks = \App\Models\MaterialChunk::withoutGlobalScopes()
                ->where('institute_id', $material->institute_id)
                ->where('page_number', $this->pageNumber)
                ->where('is_ocr', true)
                ->whereHas('material', function ($q) use ($material) {
                    $q->where('file_hash', $material->file_hash)
                      ->where('id', '!=', $material->id)
                      ->where('rag_status', 'ready');
                })
                ->orderBy('id')
                ->get();

            if ($existingChunks->isNotEmpty()) {
                $reusedText = $existingChunks->pluck('content')->implode("\n\n");
                Log::info("ProcessMaterialOcrPageJob: Reusing existing OCR result for material #{$this->materialId} page {$this->pageNumber} from identical file_hash.");
                Storage::disk('local')->put($tempPath, json_encode([
                    'page_number' => $this->pageNumber,
                    'text' => trim($reusedText),
                    'used_ocr' => true,
                    'ocr_reused' => true,
                    'ocr_uncertain' => false,
                    'word_count' => count(preg_split('/\s+/u', trim($reusedText), -1, PREG_SPLIT_NO_EMPTY) ?: []),
                    'completed_at' => now()->timestamp,
                ]));
                return;
            }
        }

        $isTemporary = false;
        $localPath = $extractor->resolveLocalFile($material, $isTemporary);

        try {
            $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            $imageData = null;
            $mimeType = 'image/jpeg';

            if ($extension === 'pdf') {
                $parser = new Parser();
                $pdf = $parser->parseFile($localPath);
                $pages = $pdf->getPages();

                if (!isset($pages[$this->pageNumber - 1])) {
                    throw new \RuntimeException("Page {$this->pageNumber} does not exist in PDF (total pages: " . count($pages) . ").");
                }

                $page = $pages[$this->pageNumber - 1];
                $xObjects = $page->getXObjects();
                foreach ($xObjects as $obj) {
                    if (method_exists($obj, 'getContent')) {
                        $content = $obj->getContent();
                        if (!empty($content) && strlen($content) > 1000) {
                            $imageData = $content;
                            break;
                        }
                    }
                }

                if (!$imageData) {
                    Log::warning("ProcessMaterialOcrPageJob: No embedded image found on page {$this->pageNumber} of material #{$this->materialId}.");
                    $imageData = '';
                }
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $imageData = file_get_contents($localPath);
                $mimeType = match ($extension) {
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };
            } else {
                throw new \RuntimeException("Unsupported file extension .{$extension} for OCR.");
            }

            $ocrText = '';
            $isUncertain = false;
            if (!empty($imageData)) {
                Log::info("ProcessMaterialOcrPageJob: Running Gemini OCR for material #{$this->materialId} page {$this->pageNumber}/{$this->totalPages} (pass 1)...");
                $ocrText1 = $geminiClient->ocrPage($imageData, $mimeType);
                $ocrText = $ocrText1;

                $enableSafeguard = (bool) config('rag.ingestion.ocr_enable_safeguard', true);
                $diffThreshold = (float) config('rag.ingestion.ocr_completeness_diff_threshold', 0.10);

                if ($enableSafeguard) {
                    try {
                        Log::info("ProcessMaterialOcrPageJob: Running Gemini OCR safeguard check for material #{$this->materialId} page {$this->pageNumber} (pass 2)...");
                        $ocrText2 = $geminiClient->ocrPage($imageData, $mimeType);

                        $len1 = mb_strlen($ocrText1, 'UTF-8');
                        $len2 = mb_strlen($ocrText2, 'UTF-8');
                        $maxLen = max($len1, $len2);
                        $diffRatio = $maxLen > 0 ? (abs($len1 - $len2) / $maxLen) : 0.0;

                        if ($diffRatio > $diffThreshold) {
                            $isUncertain = true;
                            // Keep the longer result
                            $ocrText = ($len1 >= $len2) ? $ocrText1 : $ocrText2;
                            Log::warning("ProcessMaterialOcrPageJob: OCR discrepancy of " . round($diffRatio * 100, 1) . "% on page {$this->pageNumber} of material #{$this->materialId}. Marked ocr_uncertain.");
                        } else {
                            $ocrText = ($len1 >= $len2) ? $ocrText1 : $ocrText2;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("ProcessMaterialOcrPageJob: Safeguard pass skipped for material #{$this->materialId} page {$this->pageNumber}: " . $e->getMessage());
                    }
                }
            }

            Storage::disk('local')->put($tempPath, json_encode([
                'page_number' => $this->pageNumber,
                'text' => trim($ocrText),
                'used_ocr' => true,
                'ocr_uncertain' => $isUncertain,
                'word_count' => count(preg_split('/\s+/u', trim($ocrText), -1, PREG_SPLIT_NO_EMPTY) ?: []),
                'completed_at' => now()->timestamp,
            ]));

            Log::info("ProcessMaterialOcrPageJob: Completed OCR for material #{$this->materialId} page {$this->pageNumber}/{$this->totalPages} (" . mb_strlen($ocrText) . " chars).");

        } catch (OcrUnavailableException $e) {
            $formattedError = $e->getFormattedRagError();
            Log::warning("ProcessMaterialOcrPageJob: OCR unavailable for material #{$this->materialId} page {$this->pageNumber}: {$formattedError}");
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => $formattedError,
            ]);
            throw $e;
        } catch (Throwable $e) {
            $cleanMsg = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $e->getMessage());
            Log::error("ProcessMaterialOcrPageJob: Exception on material #{$this->materialId} page {$this->pageNumber}: {$cleanMsg}");
            throw $e;
        } finally {
            if ($isTemporary && file_exists($localPath)) {
                @unlink($localPath);
            }
        }
    }

    /**
     * Handle job failure when retries exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $material = LearningMaterial::withoutGlobalScopes()->find($this->materialId);
        if ($material) {
            $cleanMsg = $exception ? $exception->getMessage() : 'OCR job failed permanently.';
            $cleanMsg = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $cleanMsg);
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => "Page {$this->pageNumber} OCR failed: " . mb_substr(strip_tags($cleanMsg), 0, 500),
            ]);
        }
    }
}
