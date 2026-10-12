<?php

namespace App\Services\Rag;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\LearningMaterial;
use App\Services\FirebaseStorageUrlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    protected GeminiClientInterface $geminiClient;
    protected int $maxPages;
    protected int $maxFileSizeMb;
    protected int $ocrMinWords;
    protected int $downloadTimeout;

    public function __construct(GeminiClientInterface $geminiClient)
    {
        $this->geminiClient = $geminiClient;
        $this->maxPages = (int) config('rag.ingestion.max_pages_per_material', 50);
        $this->maxFileSizeMb = (int) config('rag.ingestion.max_file_size_mb', 50);
        $this->ocrMinWords = (int) config('rag.ingestion.ocr_min_words_per_page', 20);
        $this->downloadTimeout = (int) config('rag.ingestion.download_timeout', 30);
    }

    /**
     * Extract text page-by-page from a learning material's document.
     *
     * @param LearningMaterial $material
     * @return array<int, array{page_number: int, text: string}>
     */
    public function extract(LearningMaterial $material): array
    {
        $localPath = null;
        $isTemporary = false;

        try {
            $localPath = $this->resolveLocalFile($material, $isTemporary);

            if (!file_exists($localPath)) {
                throw new RuntimeException("Material file not found at: {$localPath}");
            }

            $fileSize = filesize($localPath);
            $maxBytes = $this->maxFileSizeMb * 1024 * 1024;
            if ($fileSize > $maxBytes) {
                throw new RuntimeException("File exceeds maximum allowed size of {$this->maxFileSizeMb}MB (actual: " . round($fileSize / 1024 / 1024, 2) . "MB).");
            }

            return $this->extractFromPdfFile($localPath);

        } finally {
            if ($isTemporary && $localPath && file_exists($localPath)) {
                @unlink($localPath);
            }
        }
    }

    /**
     * Parse PDF file using Smalot Parser with Gemini Vision OCR fallback for scanned pages.
     *
     * @param string $filePath
     * @return array<int, array{page_number: int, text: string}>
     */
    public function extractFromPdfFile(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();

        $pageResults = [];
        $pageIndex = 0;

        foreach ($pages as $page) {
            $pageIndex++;
            if ($pageIndex > $this->maxPages) {
                Log::info("PdfTextExtractor: reached maximum page limit of {$this->maxPages}, skipping remaining pages.");
                break;
            }

            $extractedText = trim($page->getText() ?? '');
            $wordCount = count(preg_split('/\s+/u', $extractedText, -1, PREG_SPLIT_NO_EMPTY));

            // If text is sparse (scanned page or image PDF), attempt Gemini Vision OCR
            if ($wordCount < $this->ocrMinWords) {
                $ocrText = $this->attemptPageOcr($page);
                if (!empty($ocrText)) {
                    $extractedText = $ocrText;
                }
            }

            if (!empty(trim($extractedText))) {
                $pageResults[] = [
                    'page_number' => $pageIndex,
                    'text' => $extractedText,
                ];
            }
        }

        return $pageResults;
    }

    /**
     * Resolve file path locally, safely downloading from Firebase if needed.
     */
    protected function resolveLocalFile(LearningMaterial $material, bool &$isTemporary): string
    {
        $filePath = (string) $material->file_path;

        if (empty($filePath)) {
            throw new RuntimeException('Material has no file path configured.');
        }

        // Firebase Storage URL
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            $instituteId = (int) ($material->institute_id ?? 1);
            if (!FirebaseStorageUrlValidator::isValidForInstitute($filePath, $instituteId)) {
                throw new RuntimeException('Unauthorized or invalid Firebase Storage URL for this institute.');
            }

            return $this->downloadRemoteFile($filePath, $isTemporary);
        }

        // Local storage paths
        if (Storage::disk('public')->exists($filePath)) {
            $isTemporary = false;
            return Storage::disk('public')->path($filePath);
        }

        $publicUploadPath = public_path($filePath);
        if (file_exists($publicUploadPath)) {
            $isTemporary = false;
            return $publicUploadPath;
        }

        $directPath = storage_path('app/' . $filePath);
        if (file_exists($directPath)) {
            $isTemporary = false;
            return $directPath;
        }

        throw new RuntimeException("Could not locate local material file: {$filePath}");
    }

    /**
     * Safely download remote document to a temporary file with timeout and size cap.
     */
    protected function downloadRemoteFile(string $url, bool &$isTemporary): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'rag_mat_') . '.pdf';
        $isTemporary = true;

        $response = Http::timeout($this->downloadTimeout)
            ->sink($tempPath)
            ->get($url);

        if (!$response->successful()) {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            throw new RuntimeException("Failed to download remote document ({$response->status()}): {$url}");
        }

        return $tempPath;
    }

    /**
     * Attempt Gemini Vision OCR on embedded page images if text layer is missing.
     */
    protected function attemptPageOcr(Page $page): ?string
    {
        try {
            $xObjects = $page->getXObjects();
            foreach ($xObjects as $obj) {
                // Check if object is an image
                if (method_exists($obj, 'getContent')) {
                    $content = $obj->getContent();
                    if (!empty($content) && strlen($content) > 1000) {
                        return $this->geminiClient->ocrPage($content, 'image/jpeg');
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OCR fallback encountered an exception: ' . $e->getMessage());
        }

        return null;
    }
}
