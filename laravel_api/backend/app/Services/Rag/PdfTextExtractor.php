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
use ZipArchive;

class FileTooLargeException extends RuntimeException {}
class PageLimitExceededException extends RuntimeException {}
class OcrPageLimitExceededException extends RuntimeException {}
class UnsupportedFileTypeException extends RuntimeException {}

class PdfTextExtractor
{
    protected GeminiClientInterface $geminiClient;
    protected int $maxPages;
    protected int $maxFileSizeMb;
    protected int $ocrMaxPages;
    protected int $ocrMinWords;
    protected int $downloadTimeout;
    protected float $legacyFontMinScriptRatio;
    protected int $legacyFontMinLetters;
    protected int $ocrTotalTimeout;
    protected ?float $currentOcrStartTime = null;

    public function __construct(GeminiClientInterface $geminiClient)
    {
        $this->geminiClient = $geminiClient;
        $this->maxPages = (int) config('rag.ingestion.max_pages_per_material', 50);
        $this->maxFileSizeMb = (int) config('rag.ingestion.max_file_size_mb', 50);
        $this->ocrMaxPages = (int) config('rag.ingestion.ocr_max_pages', 20);
        $this->ocrMinWords = (int) config('rag.ingestion.ocr_min_words_per_page', 20);
        $this->downloadTimeout = (int) config('rag.ingestion.download_timeout', 30);
        $this->legacyFontMinScriptRatio = (float) config('rag.ingestion.legacy_font_min_script_ratio', 0.20);
        $this->legacyFontMinLetters = (int) config('rag.ingestion.legacy_font_min_letters', 50);
        $this->ocrTotalTimeout = (int) config('rag.ingestion.ocr_total_timeout', 60);
    }

    /**
     * Extract text page-by-page from a learning material's document.
     *
     * @param LearningMaterial $material
     * @return array<int, array{page_number: int, text: string}>
     */
    public function extract(LearningMaterial $material, bool $forceOcr = false): array
    {
        $this->currentOcrStartTime = null;
        $localPath = null;
        $isTemporary = false;

        try {
            $localPath = $this->resolveLocalFile($material, $isTemporary);

            if (!file_exists($localPath)) {
                throw new RuntimeException("Material file not found at: {$localPath}");
            }

            if (empty($material->file_hash) && \Illuminate\Support\Facades\Schema::hasColumn('learning_materials', 'file_hash')) {
                $fileHash = hash_file('sha256', $localPath);
                if ($fileHash) {
                    $material->updateQuietly(['file_hash' => $fileHash]);
                    $material->file_hash = $fileHash;
                }
            }

            $fileSize = filesize($localPath);
            $maxBytes = $this->maxFileSizeMb * 1024 * 1024;
            if ($fileSize > $maxBytes) {
                throw new FileTooLargeException("File exceeds maximum allowed size of {$this->maxFileSizeMb}MB (actual: " . round($fileSize / 1024 / 1024, 2) . "MB).");
            }

            $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));

            // 1. PDF Documents
            if ($extension === 'pdf') {
                return $this->extractFromPdfFile($localPath, $material->medium, $forceOcr);
            }

            // 2. Images (Scanned notes, photo past papers)
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                return $this->extractFromImageFile($localPath, $extension);
            }

            // 3. Word Documents (.docx)
            if ($extension === 'docx') {
                return $this->extractFromDocxFile($localPath);
            }

            throw new UnsupportedFileTypeException("Unsupported file type (.{$extension}). Only PDF, DOCX, and image documents are indexed for study search.");

        } finally {
            if ($isTemporary && $localPath && file_exists($localPath)) {
                @unlink($localPath);
            }
        }
    }

    /**
     * Inspect pages of a learning material document and separate into native pages and pages needing OCR.
     *
     * @param LearningMaterial $material
     * @return array{total_pages: int, native_pages: array, pages_needing_ocr: array}
     */
    public function inspectPagesForIngestion(LearningMaterial $material): array
    {
        $isTemporary = false;
        $localPath = $this->resolveLocalFile($material, $isTemporary);

        if (!file_exists($localPath)) {
            throw new RuntimeException("Material file not found at: {$localPath}");
        }

        if (empty($material->file_hash) && \Illuminate\Support\Facades\Schema::hasColumn('learning_materials', 'file_hash')) {
            $fileHash = hash_file('sha256', $localPath);
            if ($fileHash) {
                $material->updateQuietly(['file_hash' => $fileHash]);
                $material->file_hash = $fileHash;
            }
        }

        $fileSize = filesize($localPath);
        $maxBytes = $this->maxFileSizeMb * 1024 * 1024;
        if ($fileSize > $maxBytes) {
            throw new FileTooLargeException("File exceeds maximum allowed size of {$this->maxFileSizeMb}MB (actual: " . round($fileSize / 1024 / 1024, 2) . "MB).");
        }

        $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($localPath);
                $pages = $pdf->getPages();
            } catch (\Throwable $e) {
                throw new RuntimeException("Failed to parse PDF document: " . $e->getMessage(), 0, $e);
            }

            $totalPages = count($pages);
            if ($totalPages > $this->maxPages) {
                throw new PageLimitExceededException("Document exceeds maximum allowed limit of {$this->maxPages} pages (actual: {$totalPages}).");
            }

            $nativePages = [];
            $pagesNeedingOcr = [];

            foreach ($pages as $index => $page) {
                $pageNum = $index + 1;
                $rawText = trim($page->getText() ?? '');
                $extractedText = mb_convert_encoding($rawText, 'UTF-8', 'UTF-8');
                $words = @preg_split('/\s+/u', $extractedText, -1, PREG_SPLIT_NO_EMPTY);
                $wordCount = is_array($words) ? count($words) : 0;
                $isGarbled = $this->isTextGarbled($extractedText, $material->medium);

                if ($wordCount >= $this->ocrMinWords && !$isGarbled) {
                    $nativePages[] = [
                        'page_number' => $pageNum,
                        'text' => $extractedText,
                        'used_ocr' => false,
                        'word_count' => $wordCount,
                    ];
                } else {
                    $pagesNeedingOcr[] = $pageNum;
                }
            }

            return [
                'total_pages' => $totalPages,
                'native_pages' => $nativePages,
                'pages_needing_ocr' => $pagesNeedingOcr,
            ];
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            return [
                'total_pages' => 1,
                'native_pages' => [],
                'pages_needing_ocr' => [1],
            ];
        }

        if ($extension === 'docx') {
            $extracted = $this->extractFromDocxFile($localPath);
            return [
                'total_pages' => 1,
                'native_pages' => $extracted,
                'pages_needing_ocr' => [],
            ];
        }

        throw new UnsupportedFileTypeException("Unsupported file type (.{$extension}). Only PDF, DOCX, and image documents are indexed for study search.");
    }

    /**
     * Compute per-page quality ratio comparing clean script characters (ASCII, Unicode Tamil, Unicode Sinhala,
     * and math/science symbols like Ã—, Ã·, Â°, Âµ) against Latin-1 mojibake characters.
     *
     * In Sri Lankan educational materials, legacy non-Unicode fonts (such as Bamini for Tamil or FMAbhaya for Sinhala)
     * map glyphs onto high Latin-1 code points (U+00C0-U+00FF: Ã€-Ã¿). Math/science symbols like Ã— (\x{00D7}), Ã· (\x{00F7}),
     * Â° (\x{00B0}), and Âµ (\x{00B5}) are explicitly excluded from mojibake counts.
     *
     * @param string $text
     * @return float Value between 0.0 (entirely garbled mojibake) and 1.0 (clean Unicode/ASCII text)
     */
    public function computeQualityRatio(string $text): float
    {
        $len = mb_strlen($text, 'UTF-8');
        if ($len === 0) {
            return 1.0;
        }

        // Count Latin-1 mojibake glyphs (U+00C0 through U+00FF, strictly excluding math symbols Ã— (\x{00D7}) and Ã· (\x{00F7}))
        $mojibakeCount = preg_match_all('/[\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{00FF}]/u', $text);

        // Count valid clean characters: ASCII printable + Tamil script (U+0B80-U+0BFF) + Sinhala script (U+0D80-U+0DFF)
        // + Math/Science symbols (Ã—, Ã·, Â°, Âµ, Â±, Â², Â³, \p{Sm}) + whitespace
        $validCount = preg_match_all('/[\x{0020}-\x{007E}\x{0B80}-\x{0BFF}\x{0D80}-\x{0DFF}\x{00B0}\x{00B1}\x{00B2}\x{00B3}\x{00B5}\x{00D7}\x{00F7}\p{Sm}\r\n\t]/u', $text);

        $validRatio = $validCount / $len;
        $mojibakeRatio = $mojibakeCount / $len;

        return max(0.0, min(1.0, $validRatio - ($mojibakeRatio * 2)));
    }

    /**
     * Check if a page belonging to a Tamil or Sinhala medium document has an abnormally low share
     * of native script Unicode letters despite having substantial text (legacy ASCII/Bamini font symptom).
     *
     * @param string $text
     * @param string|null $medium
     * @return bool
     */
    public function isMediumScriptRatioDeficient(string $text, ?string $medium = null): bool
    {
        if (empty($medium)) {
            return false;
        }

        $normMedium = strtolower(trim($medium));
        if (!in_array($normMedium, ['tamil', 'sinhala'], true)) {
            return false;
        }

        // Count total Unicode letters (alphabetic letters across all scripts)
        preg_match_all('/\p{L}/u', $text, $letterMatches);
        $totalLetters = count($letterMatches[0] ?? []);

        // Only evaluate if the page has substantial text
        if ($totalLetters < $this->legacyFontMinLetters) {
            return false;
        }

        $scriptLetters = 0;
        if ($normMedium === 'tamil') {
            preg_match_all('/[\x{0B80}-\x{0BFF}]/u', $text, $scriptMatches);
            $scriptLetters = count($scriptMatches[0] ?? []);
        } elseif ($normMedium === 'sinhala') {
            preg_match_all('/[\x{0D80}-\x{0DFF}]/u', $text, $scriptMatches);
            $scriptLetters = count($scriptMatches[0] ?? []);
        }

        $scriptRatio = $scriptLetters / $totalLetters;

        return $scriptRatio < $this->legacyFontMinScriptRatio;
    }

    /**
     * Determine whether extracted page text is garbled legacy-font text requiring Gemini Vision OCR.
     * Evaluates Latin-1 mojibake glyphs and quality ratio (< 0.70).
     * Note: Medium/script mismatch alone does NOT trigger OCR here (fail-closed order of checks).
     *
     * @param string $text
     * @param string|null $medium
     * @return bool
     */
    public function isTextGarbled(string $text, ?string $medium = null): bool
    {
        $nonSpaceLen = mb_strlen(preg_replace('/\s+/u', '', $text), 'UTF-8');
        if ($nonSpaceLen < 5) {
            return false;
        }

        // 1. Mojibake density check (Latin-1 characters misused by legacy fonts, excluding math/science symbols like Ã— and Ã·)
        $mojibakeCount = preg_match_all('/[\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{00FF}]/u', $text);
        $mojibakeRatio = $mojibakeCount / $nonSpaceLen;

        // If >= 3 mojibake characters and ratio >= 8%, it is unmistakably legacy font mojibake
        if ($mojibakeCount >= 3 && $mojibakeRatio >= 0.08) {
            return true;
        }

        // Overall quality ratio below 0.70 indicates heavy mojibake or distorted font encoding
        if ($this->computeQualityRatio($text) < 0.70) {
            return true;
        }

        if ($medium && $this->isMediumScriptRatioDeficient($text, $medium)) {
            return true;
        }

        return false;
    }

    /**
     * Parse PDF file using Smalot Parser with Gemini Vision OCR fallback for scanned pages and garbled fonts.
     */
    public function extractFromPdfFile(string $filePath, ?string $medium = null, bool $forceOcr = false): array
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
        } catch (\Throwable $e) {
            Log::warning("PdfTextExtractor: Failed to parse PDF ({$filePath}): " . $e->getMessage());
            return [];
        }

        $totalPages = count($pages);
        if ($totalPages > $this->maxPages) {
            throw new PageLimitExceededException("Document exceeds maximum allowed limit of {$this->maxPages} pages (actual: {$totalPages} pages).");
        }

        $pageResults = [];
        $pageIndex = 0;
        $ocrCount = 0;

        foreach ($pages as $page) {
            $pageIndex++;

            $rawText = trim($page->getText() ?? '');
            $extractedText = mb_convert_encoding($rawText, 'UTF-8', 'UTF-8');
            $splitWords = @preg_split('/\s+/u', $extractedText, -1, PREG_SPLIT_NO_EMPTY);
            $wordCount = is_array($splitWords) ? count($splitWords) : 0;
            $isGarbled = $this->isTextGarbled($extractedText, $medium);
            $usedOcr = false;

            // If text is sparse (scanned page or image PDF), garbled legacy font, or forceOcr requested
            if ($forceOcr || $wordCount < $this->ocrMinWords || $isGarbled) {
                if ($ocrCount >= $this->ocrMaxPages) {
                    throw new OcrPageLimitExceededException("Document exceeds maximum OCR limit of {$this->ocrMaxPages} pages.");
                }
                $this->checkOcrTimeBudget();
                $ocrText = $this->attemptPageOcr($page);
                $this->checkOcrTimeBudget();
                $ocrCount++;
                if (!empty($ocrText)) {
                    $extractedText = $ocrText;
                    $usedOcr = true;
                }
            }

            if (!empty(trim($extractedText))) {
                $pageResults[] = [
                    'page_number' => $pageIndex,
                    'text' => $extractedText,
                    'used_ocr' => $usedOcr,
                ];
            }
        }

        return $pageResults;
    }

    /**
     * Check whether total OCR execution time for current material has exceeded configured limit.
     */
    public function checkOcrTimeBudget(): void
    {
        if ($this->currentOcrStartTime === null) {
            $this->currentOcrStartTime = microtime(true);
        } elseif ((microtime(true) - $this->currentOcrStartTime) > $this->ocrTotalTimeout) {
            throw new OcrTimeLimitException('OCR time limit, retry later');
        }
    }

    /**
     * Extract text directly from an image note using Gemini Vision OCR.
     */
    protected function extractFromImageFile(string $filePath, string $extension): array
    {
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        $imageData = file_get_contents($filePath);
        if (empty($imageData)) {
            return [];
        }

        $this->checkOcrTimeBudget();
        $ocrText = $this->geminiClient->ocrPage($imageData, $mimeType);
        $this->checkOcrTimeBudget();

        if (empty(trim($ocrText))) {
            return [];
        }

        return [
            [
                'page_number' => null,
                'text' => trim($ocrText),
                'used_ocr' => true,
            ],
        ];
    }

    /**
     * Extract plain text from Word Document (.docx) via standard ZipArchive.
     */
    protected function extractFromDocxFile(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Unable to open DOCX archive.');
        }

        $xmlIndex = $zip->locateName('word/document.xml');
        if ($xmlIndex === false) {
            $zip->close();
            throw new RuntimeException('Invalid DOCX format: word/document.xml not found.');
        }

        $xmlData = $zip->getFromIndex($xmlIndex);
        $zip->close();

        // Strip XML tags while preserving paragraph spacing
        $text = preg_replace('/<\/w:p>/u', "\n\n", $xmlData);
        $text = strip_tags($text);
        $cleanText = trim(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

        if (empty($cleanText)) {
            return [];
        }

        return [
            [
                'page_number' => null,
                'text' => $cleanText,
                'used_ocr' => false,
            ],
        ];
    }

    /**
     * Resolve file path locally, safely downloading from Firebase if needed.
     */
    public function resolveLocalFile(LearningMaterial $material, bool &$isTemporary): string
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
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'pdf';
        $tempPath = tempnam(sys_get_temp_dir(), 'rag_mat_') . '.' . $extension;
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
                if (method_exists($obj, 'getContent')) {
                    $content = $obj->getContent();
                    if (!empty($content) && strlen($content) > 1000) {
                        return $this->geminiClient->ocrPage($content, 'image/jpeg');
                    }
                }
            }
        } catch (OcrUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('OCR fallback encountered an exception: ' . $e->getMessage());
        }

        return null;
    }
}


