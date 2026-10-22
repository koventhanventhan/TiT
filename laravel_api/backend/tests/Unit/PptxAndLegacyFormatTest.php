<?php

namespace Tests\Unit;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Services\Rag\PdfTextExtractor;
use App\Services\Rag\UnsupportedFileTypeException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PptxAndLegacyFormatTest extends TestCase
{
    use RefreshDatabase;

    private PdfTextExtractor $extractor;
    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();

        $geminiClient = new class implements GeminiClientInterface {
            public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array { return []; }
            public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array { return []; }
            public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string { return ''; }
            public function generateAnswer(string $prompt, ?string $systemInstruction = null): string { return ''; }
            public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void {}
        };

        $this->extractor = new PdfTextExtractor($geminiClient);
        $this->institute = Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-' . uniqid(),
            'is_active' => true,
        ]);
    }

    public function test_legacy_doc_and_ppt_throw_unsupported_exception_with_clear_message(): void
    {
        $tempDoc = tempnam(sys_get_temp_dir(), 'test_') . '.doc';
        file_put_contents($tempDoc, 'Binary legacy word file');

        $matDoc = LearningMaterial::create([
            'title' => 'Legacy Word Document',
            'type' => 'document',
            'grade' => 'grade-10',
            'subject' => 'English',
            'medium' => 'english',
            'file_path' => $tempDoc,
            'institute_id' => $this->institute->id,
        ]);

        try {
            $this->expectException(UnsupportedFileTypeException::class);
            $this->expectExceptionMessage('Legacy format (.doc) is unsupported, please upload PDF or DOCX.');
            $this->extractor->inspectPagesForIngestion($matDoc);
        } finally {
            @unlink($tempDoc);
        }
    }

    public function test_legacy_ppt_throws_unsupported_exception(): void
    {
        $tempPpt = tempnam(sys_get_temp_dir(), 'test_') . '.ppt';
        file_put_contents($tempPpt, 'Binary legacy powerpoint file');

        $matPpt = LearningMaterial::create([
            'title' => 'Legacy PowerPoint Presentation',
            'type' => 'document',
            'grade' => 'grade-10',
            'subject' => 'Science',
            'medium' => 'english',
            'file_path' => $tempPpt,
            'institute_id' => $this->institute->id,
        ]);

        try {
            $this->expectException(UnsupportedFileTypeException::class);
            $this->expectExceptionMessage('Legacy format (.ppt) is unsupported, please upload PDF or DOCX.');
            $this->extractor->inspectPagesForIngestion($matPpt);
        } finally {
            @unlink($tempPpt);
        }
    }

    public function test_pptx_slides_extracted_in_order_with_page_number_equal_to_slide_number(): void
    {
        $tempPptx = tempnam(sys_get_temp_dir(), 'test_pptx_') . '.pptx';

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempPptx, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        // Create slide 2 first to verify numerical ordering
        $slide2Xml = '<p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><p:cSld><p:spTree><p:sp><p:txBody><a:p><a:t>Slide 2: Newton Laws of Motion</a:t></a:p><a:p><a:t>F = ma is the second law.</a:t></a:p></p:txBody></p:sp></p:spTree></p:cSld></p:sld>';
        $zip->addFromString('ppt/slides/slide2.xml', $slide2Xml);

        // Create slide 1
        $slide1Xml = '<p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><p:cSld><p:spTree><p:sp><p:txBody><a:p><a:t>Slide 1: Introduction to Mechanics</a:t></a:p></p:txBody></p:sp></p:spTree></p:cSld></p:sld>';
        $zip->addFromString('ppt/slides/slide1.xml', $slide1Xml);

        // Add dummy image in ppt/media to verify images are safely ignored
        $zip->addFromString('ppt/media/image1.png', 'fake image bytes');
        $zip->close();

        $mat = LearningMaterial::create([
            'title' => 'Mechanics Lecture Slides',
            'type' => 'document',
            'grade' => 'grade-12',
            'subject' => 'Physics',
            'medium' => 'english',
            'file_path' => $tempPptx,
            'institute_id' => $this->institute->id,
        ]);

        try {
            $inspection = $this->extractor->inspectPagesForIngestion($mat);
            $this->assertEquals(2, $inspection['total_pages']);
            $this->assertCount(2, $inspection['native_pages']);
            $this->assertEmpty($inspection['pages_needing_ocr']);

            $pages = $inspection['native_pages'];
            // Verify numerical slide order
            $this->assertEquals(1, $pages[0]['page_number']);
            $this->assertStringContainsString('Slide 1: Introduction to Mechanics', $pages[0]['text']);

            $this->assertEquals(2, $pages[1]['page_number']);
            $this->assertStringContainsString('Slide 2: Newton Laws of Motion', $pages[1]['text']);
            $this->assertStringContainsString('F = ma is the second law', $pages[1]['text']);
        } finally {
            @unlink($tempPptx);
        }
    }
}
