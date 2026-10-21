<?php

namespace App\Console\Commands;

use App\Models\LearningMaterial;
use App\Services\Rag\TextReadabilityChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class RagOcrTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:ocr-test {id : Learning material ID} {page=1 : Page number to test (default 1)} {--model= : Specific model to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'OCR test a single page of a learning material and print diagnostic telemetry';

    /**
     * Execute the console command.
     */
    public function handle(TextReadabilityChecker $readabilityChecker): int
    {
        $id = (int) $this->argument('id');
        $targetPage = max(1, (int) $this->argument('page'));

        $material = LearningMaterial::withoutGlobalScopes()->find($id);
        if (!$material) {
            $this->error("Material #{$id} not found.");
            return 1;
        }

        $filePath = (string) $material->file_path;
        $localPath = null;
        if (Storage::disk('public')->exists($filePath)) {
            $localPath = Storage::disk('public')->path($filePath);
        } elseif (file_exists(public_path($filePath))) {
            $localPath = public_path($filePath);
        } elseif (file_exists(storage_path('app/' . $filePath))) {
            $localPath = storage_path('app/' . $filePath);
        }

        if (!$localPath || !file_exists($localPath)) {
            $this->error("File for material #{$id} could not be located locally.");
            return 1;
        }

        $extension = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        $imageData = null;
        $mimeType = 'image/jpeg';

        if ($extension === 'pdf') {
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($localPath);
                $pages = $pdf->getPages();
            } catch (\Throwable $e) {
                $this->error("Failed to parse PDF: " . $e->getMessage());
                return 1;
            }

            if (!isset($pages[$targetPage - 1])) {
                $this->error("Page {$targetPage} does not exist (total pages: " . count($pages) . ").");
                return 1;
            }

            $page = $pages[$targetPage - 1];
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
                $this->error("No embedded image stream found on page {$targetPage}.");
                return 1;
            }
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $imageData = file_get_contents($localPath);
            $mimeType = match ($extension) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
        } else {
            $this->error("Unsupported file extension .{$extension} for OCR testing.");
            return 1;
        }

                // Downscale image if exceeds max dimension
        $maxDim = (int) config('rag.ingestion.ocr_max_dimension', 1600);
        $jpegQuality = (int) config('rag.ingestion.ocr_jpeg_quality', 80);
        if (function_exists('imagecreatefromstring')) {
            $img = @imagecreatefromstring($imageData);
            if ($img) {
                $w = imagesx($img);
                $h = imagesy($img);
                if ($w > $maxDim || $h > $maxDim) {
                    $scale = min($maxDim / $w, $maxDim / $h);
                    $newW = (int) round($w * $scale);
                    $newH = (int) round($h * $scale);
                    $resized = imagecreatetruecolor($newW, $newH);
                    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                    ob_start();
                    imagejpeg($resized, null, $jpegQuality);
                    $downscaled = ob_get_clean();
                    imagedestroy($img);
                    imagedestroy($resized);
                    if ($downscaled) {
                        $imageData = $downscaled;
                    }
                } else {
                    imagedestroy($img);
                }
            }
        }

        $imageSizeKb = round(strlen($imageData) / 1024, 2);

        $apiKey = (string) (config('services.gemini.api_key') ?: env('GEMINI_API_KEY'));
        if (empty($apiKey)) {
            $this->error("Gemini API key is not configured.");
            return 1;
        }

        $model = (string) ($this->option('model') ?: config('rag.models.ocr') ?: config('rag.models.llm', 'gemini-3.5-flash'));
        $ocrTemperature = (float) config('rag.ocr.temperature', 0.0);

        $prompt = 'You are a strict OCR transcription engine. Transcribe all text from this scanned document page exactly and verbatim without correcting spelling, translating, or summarising. Preserve all characters, layout, Tamil (தமிழ்), Sinhala (සිංහල), and English text, formulas, punctuation, and keep all numbers and dates exactly as printed. Output ONLY the extracted text. Do not add markdown commentary, introductory greetings, explanations, or notes.';

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => base64_encode($imageData),
                            ],
                        ],
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $ocrTemperature,
            ],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $startTime = microtime(true);
        $response = null;
        $maxTries = 3;
        for ($try = 1; $try <= $maxTries; $try++) {
            try {
                $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->withOptions([
                    'connect_timeout' => 30,
                ])->timeout(120)->post($url, $payload);

                if ($response->successful()) {
                    break;
                }

                if (in_array($response->status(), [429, 503, 500]) && $try < $maxTries) {
                    $this->warn("Received HTTP " . $response->status() . " (temporary load), retrying in 3s (attempt {$try}/{$maxTries})...");
                    sleep(3);
                    continue;
                }
                break;
            } catch (\Throwable $e) {
                $cleanMsg = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $e->getMessage());
                if ($try < $maxTries) {
                    $this->warn("Network/SSL exception ({$cleanMsg}), retrying in 3s (attempt {$try}/{$maxTries})...");
                    sleep(3);
                    continue;
                }
                $this->error("Failed after {$maxTries} attempts: {$cleanMsg}");
                return 1;
            }
        }
        $elapsedTime = round(microtime(true) - $startTime, 3);
        $httpStatus = $response->status();

        if ($response->successful()) {
            $body = $response->json();
            $extractedText = trim($body['candidates'][0]['content']['parts'][0]['text'] ?? '');
            $charCount = mb_strlen($extractedText);
            $textSha256 = hash('sha256', $extractedText);

            $readability = $readabilityChecker->check([
                ['page_number' => $targetPage, 'text' => $extractedText]
            ], $material->medium);

            $readabilityScore = $readability['score'] ?? 0.0;

            $this->line("HTTP status: {$httpStatus}");
            $this->line("Model used: {$model}");
            $this->line("Temperature: {$ocrTemperature}");
            $this->line("Image size: {$imageSizeKb} KB");
            $this->line("Elapsed time: {$elapsedTime}s");
            $this->line("Character count: {$charCount}");
            $this->line("Text SHA-256: {$textSha256}");
            $this->line("Readability score: {$readabilityScore}");
            $this->newLine();
            $this->info("=== Extracted Text Preview (first 300 chars) ===");
            $this->line(mb_substr($extractedText, 0, 300));

            return 0;
        }

        // Error handling: strictly no API key, no file text printed
        $body = $response->json() ?? [];
        $googleStatus = $body['error']['status'] ?? 'UNKNOWN';
        $rawMessage = $body['error']['message'] ?? 'No message provided';

        // Sanitize any potential API key in the message
        $cleanMessage = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $rawMessage);

        $this->line("HTTP status: {$httpStatus}");
        $this->line("Google error status: {$googleStatus}");
        $this->line("Google error message: {$cleanMessage}");
        $this->line("Model used: {$model}");
        $this->line("Image size: {$imageSizeKb} KB");
        $this->line("Elapsed time: {$elapsedTime}s");

        $desc = match ($httpStatus) {
            503 => 'overloaded',
            429 => 'rate limit / quota exceeded',
            404 => 'model not found',
            500 => 'internal server error',
            default => 'unavailable'
        };

        $material->updateQuietly([
            'rag_error' => "OCR failed: {$httpStatus} {$desc}",
        ]);

        return 1;
    }
}
