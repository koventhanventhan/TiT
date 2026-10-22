<?php

namespace App\Jobs;

use App\Contracts\Rag\GeminiClientInterface;
use App\Exceptions\Rag\OcrUnavailableException;
use App\Models\LearningMaterial;
use App\Models\MaterialPage;
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
    public int $tries = 2;
    public int $timeout = 240;

    public function __construct(int $materialId, int $pageNumber, int $totalPages = 1)
    {
        $this->materialId = $materialId;
        $this->pageNumber = $pageNumber;
        $this->totalPages = $totalPages;
    }

    /**
     * Execute the per-page OCR job.
     */
    public function handle(
        GeminiClientInterface $geminiClient,
        PdfTextExtractor $extractor
    ): void {
        $tempPath = "rag_temp/material_{$this->materialId}/page_{$this->pageNumber}.json";

        // Idempotency check: if already completed in temp, skip
        if (Storage::disk('local')->exists($tempPath)) {
            $cached = json_decode(Storage::disk('local')->get($tempPath), true);
            if (!empty($cached['text'])) {
                Log::info("ProcessMaterialOcrPageJob: Page {$this->pageNumber} of material #{$this->materialId} already completed in temp, skipping OCR.");
                return;
            }
        }

        $material = LearningMaterial::withoutGlobalScopes()->find($this->materialId);
        if (!$material) {
            Log::warning("ProcessMaterialOcrPageJob: Material #{$this->materialId} not found, aborting.");
            return;
        }

        // 1. Check material_pages table for existing extracted page text
        $existingPage = MaterialPage::where('material_id', $this->materialId)
            ->where('page_number', $this->pageNumber)
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->first();

        if ($existingPage) {
            Log::info("ProcessMaterialOcrPageJob: Reusing existing page text from material_pages for material #{$this->materialId} page {$this->pageNumber}.");
            Storage::disk('local')->put($tempPath, json_encode([
                'page_number' => $this->pageNumber,
                'text' => trim($existingPage->text),
                'used_ocr' => (bool) $existingPage->is_ocr,
                'ocr_reused' => true,
                'ocr_uncertain' => (bool) $existingPage->ocr_uncertain,
                'word_count' => count(preg_split('/\s+/u', trim($existingPage->text), -1, PREG_SPLIT_NO_EMPTY) ?: []),
                'completed_at' => now()->timestamp,
            ]));
            return;
        }

        // 2. Reuse existing OCR result when the same file_hash was already stored in material_pages
        if (!empty($material->file_hash)) {
            $existingHashPage = MaterialPage::where('file_hash', $material->file_hash)
                ->where('page_number', $this->pageNumber)
                ->whereNotNull('text')
                ->where('text', '!=', '')
                ->first();

            if ($existingHashPage) {
                Log::info("ProcessMaterialOcrPageJob: Reusing existing OCR result for material #{$this->materialId} page {$this->pageNumber} from identical file_hash in material_pages.");
                Storage::disk('local')->put($tempPath, json_encode([
                    'page_number' => $this->pageNumber,
                    'text' => trim($existingHashPage->text),
                    'used_ocr' => (bool) $existingHashPage->is_ocr,
                    'ocr_reused' => true,
                    'ocr_uncertain' => (bool) $existingHashPage->ocr_uncertain,
                    'word_count' => count(preg_split('/\s+/u', trim($existingHashPage->text), -1, PREG_SPLIT_NO_EMPTY) ?: []),
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

                // Cheap quality checks to decide if a second pass is warranted:
                // 1. Text far too short for the image (< 150 chars when image data > 10KB)
                $tooShort = (mb_strlen($ocrText1, 'UTF-8') < 150 && strlen($imageData) > 10240);
                // 2. Mojibake / replacement characters
                $hasMojibake = (bool) preg_match('/[\x{FFFD}\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}]/u', $ocrText1);
                // 3. Question numbering gaps (e.g. questions found out of sequence with a skipped number)
                $hasNumberingGaps = $this->detectNumberingGaps($ocrText1);

                $needsSecondPass = $tooShort || $hasMojibake || $hasNumberingGaps;

                if ($needsSecondPass) {
                    try {
                        Log::info("ProcessMaterialOcrPageJob: Quality check triggered second OCR pass for material #{$this->materialId} page {$this->pageNumber} (short: " . ($tooShort ? 'yes' : 'no') . ", mojibake: " . ($hasMojibake ? 'yes' : 'no') . ", gaps: " . ($hasNumberingGaps ? 'yes' : 'no') . ")...");
                        $ocrText2 = $geminiClient->ocrPage($imageData, $mimeType);

                        $len1 = mb_strlen($ocrText1, 'UTF-8');
                        $len2 = mb_strlen($ocrText2, 'UTF-8');
                        $maxLen = max($len1, $len2);
                        $diffRatio = $maxLen > 0 ? (abs($len1 - $len2) / $maxLen) : 0.0;

                        $diffThreshold = (float) config('rag.ingestion.ocr_completeness_diff_threshold', 0.10);
                        if ($diffRatio > $diffThreshold) {
                            $isUncertain = true;
                            $ocrText = ($len1 >= $len2) ? $ocrText1 : $ocrText2;
                            Log::warning("ProcessMaterialOcrPageJob: OCR discrepancy of " . round($diffRatio * 100, 1) . "% on page {$this->pageNumber} of material #{$this->materialId}. Marked ocr_uncertain.");
                        } else {
                            $ocrText = ($len1 >= $len2) ? $ocrText1 : $ocrText2;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("ProcessMaterialOcrPageJob: Second pass skipped for material #{$this->materialId} page {$this->pageNumber}: " . $e->getMessage());
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
     * Detect gaps in numbered questions on a page (e.g. 1, 2, 4 without 3).
     */
    protected function detectNumberingGaps(string $text): bool
    {
        preg_match_all('/(?:^|\n)\s*(?:\(?(\d{1,3})\s*[\.\)\-]|(\d{1,3})\.\s+)/u', $text, $matches);
        $numbers = [];
        foreach ($matches[1] as $idx => $m1) {
            $val = !empty($m1) ? (int) $m1 : (int) ($matches[2][$idx] ?? 0);
            if ($val > 0) {
                $numbers[] = $val;
            }
        }

        if (count($numbers) < 3) {
            return false;
        }

        // Check if there are gaps > 1 in strictly ascending sequences
        for ($i = 0; $i < count($numbers) - 1; $i++) {
            $diff = $numbers[$i + 1] - $numbers[$i];
            if ($diff > 1 && $diff <= 3) {
                return true;
            }
        }

        return false;
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