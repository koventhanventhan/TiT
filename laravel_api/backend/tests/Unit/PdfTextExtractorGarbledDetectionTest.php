<?php

namespace Tests\Unit;

use App\Contracts\Rag\GeminiClientInterface;
use App\Services\Rag\PdfTextExtractor;
use Tests\TestCase;

class DummyGeminiClient implements GeminiClientInterface
{
    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        return array_fill(0, 768, 0.0);
    }

    public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        return [];
    }

    public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string
    {
        return 'Mock OCR Text';
    }

    public function generateAnswer(string $prompt, ?string $systemInstruction = null): string
    {
        return 'Mock Answer';
    }

    public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void
    {
        $onChunk('Mock Answer');
    }
}

class PdfTextExtractorGarbledDetectionTest extends TestCase
{
    private PdfTextExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new PdfTextExtractor(new DummyGeminiClient());
    }

    public function test_gibberish_and_latin1_mojibake_are_flagged_as_garbled(): void
    {
        // 1. High density Latin-1 mojibake (typical of Bamini legacy Tamil font extracted as Latin-1)
        $latin1Mojibake = "Ã¡Ã¢Ã£Ã¤ÀÁÂÃÄÅÈÉÊË ñòóôõ ö÷øùú ÿþýüûúùø÷öõôóòñ";
        $this->assertTrue($this->extractor->isTextGarbled($latin1Mojibake), "Latin-1 mojibake must be detected as garbled.");
        $this->assertLessThan(0.50, $this->extractor->computeQualityRatio($latin1Mojibake));

        // 2. Legacy font string with high mojibake code points
        $legacyBamini = "wOHdmk ÀÁÂÃÄÅÈÉÊË fld<U 01 ñòóôõ ö÷øùú";
        $this->assertTrue($this->extractor->isTextGarbled($legacyBamini), "Legacy font string must be detected as garbled.");
    }

    public function test_clean_english_text_is_not_garbled(): void
    {
        $englishText = "Photosynthesis is the biological process by which green plants, algae, and certain bacteria convert light energy into chemical energy stored in glucose.";
        
        $this->assertFalse($this->extractor->isTextGarbled($englishText), "Clean English text must NOT be detected as garbled.");
        $this->assertGreaterThanOrEqual(0.95, $this->extractor->computeQualityRatio($englishText));
    }

    public function test_clean_tamil_unicode_text_is_not_garbled(): void
    {
        $tamilText = "ஒளிச்சேர்க்கை என்பது தாவரங்கள் சூரிய ஒளியைப் பயன்படுத்தி உணவைத் தயாரிக்கும் செயல்முறையாகும். இது பச்சையம் உள்ள இலைகளில் நடைபெறுகிறது.";

        $this->assertFalse($this->extractor->isTextGarbled($tamilText), "Clean Tamil Unicode text must NOT be detected as garbled.");
        $this->assertGreaterThanOrEqual(0.90, $this->extractor->computeQualityRatio($tamilText));
    }

    public function test_clean_sinhala_unicode_text_is_not_garbled(): void
    {
        $sinhalaText = "ප්‍රභාසංස්ලේෂණය යනු හරිත ශාක විසින් සූර්යාලෝකය රසායනික ශක්තිය බවට පරිවර්තනය කිරීමේ ක්‍රියාවලියයි. මෙය පත්‍රවල ඇති හරිතලව තුළ සිදුවේ.";

        $this->assertFalse($this->extractor->isTextGarbled($sinhalaText), "Clean Sinhala Unicode text must NOT be detected as garbled.");
        $this->assertGreaterThanOrEqual(0.90, $this->extractor->computeQualityRatio($sinhalaText));
    }

    public function test_english_text_with_occasional_accented_loanwords_is_not_garbled(): void
    {
        // Occasional loanwords like café or résumé have valid ratio well above 70% and low mojibake count
        $loanwordText = "The student visited the local café to review their curriculum vitae before the academic conference.";

        $this->assertFalse($this->extractor->isTextGarbled($loanwordText), "Legitimate accented English loanwords must NOT be flagged as garbled.");
        $this->assertGreaterThan(0.80, $this->extractor->computeQualityRatio($loanwordText));
    }

    public function test_math_and_science_symbols_are_not_counted_as_mojibake(): void
    {
        // Math/science symbols like × (U+00D7), ÷ (U+00F7), ° (U+00B0), µ (U+00B5), ± (U+00B1), ² (U+00B2)
        // must NOT be counted as mojibake.
        $mathScienceText = "Calculate the reaction quotient: 50 × 2 ÷ 5 = 20 mol/dm³. The boiling point of water is 100 °C, and the laser wavelength is 500 µm ± 5 µm with energy 15 × 10³ J.";

        $this->assertFalse($this->extractor->isTextGarbled($mathScienceText), "Math/science formulas with ×, ÷, °, µ must NOT be flagged as garbled.");
        $this->assertGreaterThanOrEqual(0.95, $this->extractor->computeQualityRatio($mathScienceText), "Math/science characters must have near-perfect quality ratio.");
    }

    public function test_medium_aware_tamil_detects_legacy_bamini_ascii_as_garbled(): void
    {
        // Legacy Bamini Tamil font documents extract as ASCII strings (e.g. gFjp, ghlEhy;)
        // When medium='tamil', substantial text with 0% Tamil Unicode letters must be flagged as garbled.
        $baminiAsciiText = "gFjp 01 ghlEhy; gjpggfk; gy;fiyf;fof khztu;fs; gapw;rp tpdhj;jhs; njhFjp 2026 fy;tp mikr;Rf;";

        $this->assertTrue($this->extractor->isMediumScriptRatioDeficient($baminiAsciiText, 'tamil'));
        $this->assertTrue($this->extractor->isTextGarbled($baminiAsciiText, 'tamil'), "Bamini ASCII text in a Tamil medium document must trigger Vision OCR fallback.");

        // Clean Tamil text in Tamil medium document must pass cleanly
        $cleanTamilText = "உயிரியல் பாடம் 1: ஒளிச்சேர்க்கை (Photosynthesis) என்பது தாவரங்கள் சூரிய ஒளியைப் பயன்படுத்தி உணவு தயாரிக்கும் செயல்முறையாகும். சமன்பாடு: 6CO2 + 6H2O -> C6H12O6 + 6O2.";
        $this->assertFalse($this->extractor->isMediumScriptRatioDeficient($cleanTamilText, 'tamil'));
        $this->assertFalse($this->extractor->isTextGarbled($cleanTamilText, 'tamil'), "Clean Tamil text must not be flagged.");
    }

    public function test_medium_aware_sinhala_detects_legacy_fmabhaya_ascii_as_garbled(): void
    {
        // Legacy FMAbhaya Sinhala font documents extract as ASCII strings
        // When medium='sinhala', substantial text with 0% Sinhala Unicode letters must be flagged as garbled.
        $sinhalaAsciiText = "wOHdmk fld<U úNd. fomd¾;fïka;=j YS% ,xld ksoyi ch.%yKh lrk ,§ úoHd fldÜGdih 2026";

        $this->assertTrue($this->extractor->isMediumScriptRatioDeficient($sinhalaAsciiText, 'sinhala'));
        $this->assertTrue($this->extractor->isTextGarbled($sinhalaAsciiText, 'sinhala'), "FMAbhaya ASCII text in a Sinhala medium document must trigger Vision OCR fallback.");

        // Clean Sinhala text in Sinhala medium document must pass cleanly
        $cleanSinhalaText = "ජීව විද්‍යාව පාඩම 1: ප්‍රභාසංස්ලේෂණය (Photosynthesis) යනු හරිත ශාක විසින් සූර්යාලෝකය රසායනික ශක්තිය බවට පරිවර්තනය කිරීමේ ක්‍රියාවලියයි.";
        $this->assertFalse($this->extractor->isMediumScriptRatioDeficient($cleanSinhalaText, 'sinhala'));
        $this->assertFalse($this->extractor->isTextGarbled($cleanSinhalaText, 'sinhala'), "Clean Sinhala text must not be flagged.");
    }

    public function test_distress_message_includes_trusted_adult_or_teacher_even_when_no_numbers_exist(): void
    {
        // Clear all helpline numbers
        config(['rag.safety.helplines' => [
            ['name' => 'Primary Helpline', 'number' => ''],
            ['name' => 'Secondary Helpline', 'number' => ''],
            ['name' => 'Institute Support', 'number' => ''],
        ]]);

        $detector = new \App\Services\Rag\DistressDetector();

        $enMsg = $detector->getCaringMessage('en');
        $this->assertStringContainsString('talk to a trusted adult or your teacher', $enMsg);
        $this->assertStringNotContainsString('Support & Crisis Helplines:', $enMsg, 'Should not render helplines header when no numbers configured');

        $taMsg = $detector->getCaringMessage('ta');
        $this->assertStringContainsString('உடனடியாக உங்கள் ஆசிரியர் அல்லது நீங்கள் நம்பும் ஒரு பெரியவரிடம் பேசுங்கள்', $taMsg);
        $this->assertStringNotContainsString('talk to a trusted adult or your teacher', $taMsg, 'Tamil distress message must be fully in Tamil with no English in parentheses');
        $this->assertStringNotContainsString('உதவி மற்றும் ஆதரவு அவசர எண்கள்', $taMsg);

        $siMsg = $detector->getCaringMessage('si');
        $this->assertStringContainsString('ඔබ විශ්වාස කරන වැඩිහිටියෙකු හෝ ඔබගේ ගුරුවරයා සමඟ වහාම කතා කරන්න', $siMsg);
        $this->assertStringNotContainsString('talk to a trusted adult or your teacher', $siMsg, 'Sinhala distress message must be fully in Sinhala with no English in parentheses');
        $this->assertStringNotContainsString('සහාය සහ හදිසි උපකාරක දුරකථන අංක', $siMsg);
    }
}
