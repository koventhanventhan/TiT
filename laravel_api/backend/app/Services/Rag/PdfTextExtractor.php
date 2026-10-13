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
                throw new FileTooLargeException("File exceeds maximum allowed size of {$this->maxFileSizeMb}MB (actual: " . round($fileSize / 1024 / 1024, 2) . "MB).");
            }

            $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));

            // 1. PDF Documents
            if ($extension === 'pdf') {
                return $this->extractFromPdfFile($localPath, $material->medium);
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
     * Compute per-page quality ratio comparing clean script characters (ASCII, Unicode Tamil, Unicode Sinhala,
     * and math/science symbols like ×, ÷, °, µ) against Latin-1 mojibake characters.
     *
     * In Sri Lankan educational materials, legacy non-Unicode fonts (such as Bamini for Tamil or FMAbhaya for Sinhala)
     * map glyphs onto high Latin-1 code points (U+00C0-U+00FF: À-ÿ). Math/science symbols like × (\x{00D7}), ÷ (\x{00F7}),
     * ° (\x{00B0}), and µ (\x{00B5}) are explicitly excluded from mojibake counts.
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

        // Count Latin-1 mojibake glyphs (U+00C0 through U+00FF, strictly excluding math symbols × (\x{00D7}) and ÷ (\x{00F7}))
        $mojibakeCount = preg_match_all('/[\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{00FF}]/u', $text);

        // Count valid clean characters: ASCII printable + Tamil script (U+0B80-U+0BFF) + Sinhala script (U+0D80-U+0DFF)
        // + Math/Science symbols (×, ÷, °, µ, ±, ², ³, \p{Sm}) + whitespace
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

        // 1. Mojibake density check (Latin-1 characters misused by legacy fonts, excluding math/science symbols like × and ÷)
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

        // 2. Medium-aware Unicode script share check for Tamil/Sinhala documents
        if ($this->isMediumScriptRatioDeficient($text, $medium)) {
            return true;
        }

        return false;
    }

    /**
     * Parse PDF file using Smalot Parser with Gemini Vision OCR fallback for scanned pages and garbled fonts.
     */
    public function extractFromPdfFile(string $filePath, ?string $medium = null): array
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

            $extractedText = trim($page->getText() ?? '');
            $wordCount = count(preg_split('/\s+/u', $extractedText, -1, PREG_SPLIT_NO_EMPTY));
            $isGarbled = $this->isTextGarbled($extractedText, $medium);
            $usedOcr = false;

            // If text is sparse (scanned page or image PDF) OR garbled legacy font, fallback to Gemini Vision OCR
            if ($wordCount < $this->ocrMinWords || $isGarbled) {
                if ($ocrCount >= $this->ocrMaxPages) {
                    throw new OcrPageLimitExceededException("Document exceeds maximum OCR limit of {$this->ocrMaxPages} pages.");
                }
                $ocrText = $this->attemptPageOcr($page);
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

        $ocrText = $this->geminiClient->ocrPage($imageData, $mimeType);

        if (empty(trim($ocrText))) {
            return [];
        }

        return [
            [
                'page_number' => 1,
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
                'page_number' => 1,
                'text' => $cleanText,
                'used_ocr' => false,
            ],
        ];
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
        } catch (\Throwable $e) {
            Log::warning('OCR fallback encountered an exception: ' . $e->getMessage());
        }

        return null;
    }
}
