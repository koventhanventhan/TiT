<?php

namespace App\Services\Rag;

class TextNormalizer
{
    /**
     * Common Tamil stop words / interrogatives that carry no topical relevance.
     */
    protected const TAMIL_STOP_WORDS = [
        'எது', 'எவை', 'என்ன', 'எவ்வாறு', 'எங்கு', 'எப்போது', 'யாருடைய', 'யார்', 'யாது', 'யாவை', 'யாவன',
        'எத்தனை', 'எந்த', 'பற்றி', 'கூறுக', 'விளக்குக', 'குறிப்பிடுக', 'தருக', 'எழுதுக',
        'விடை', 'வினா', 'கேள்வி', 'பதில்', 'ஒரு', 'இரு', 'மற்றும்', 'அல்லது', 'என',
        'ஆகிய', 'உள்ள', 'உள்ளது', 'எனப்படுவது', 'என்பது', 'என்ற', 'இதன்', 'அதன்', 'ஏன்',
    ];

    /**
     * Common English stop words.
     */
    protected const ENGLISH_STOP_WORDS = [
        'what', 'which', 'who', 'where', 'when', 'why', 'how', 'is', 'are', 'was', 'were',
        'the', 'a', 'an', 'and', 'or', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
        'do', 'does', 'did', 'done', 'has', 'have', 'had', 'can', 'could', 'will', 'would', 'should',
        'explain', 'describe', 'give', 'state', 'name', 'tell', 'about', 'from',
    ];

    /**
     * Common Sinhala stop words / interrogatives.
     */
    protected const SINHALA_STOP_WORDS = [
        'කුමක්ද', 'මොනවාද', 'කවරේද', 'කවුද', 'කොහේද', 'කවදාද', 'ඇයි', 'කෙසේද', 'සහ', 'හෝ',
        'සඳහන්', 'කරන්න', 'විස්තර', 'පිළිබඳ', 'ගැන', 'කුමන', 'යනු', 'මොකක්ද',
    ];

    /**
     * Tokenize text into normalized keyword stems.
     *
     * @param string $text
     * @return array<string>
     */
    public function extractKeywords(string $text): array
    {
        // 1. Lowercase and clean OCR spurious virama stacked on vowel signs
        $clean = mb_strtolower($text, 'UTF-8');
        $clean = preg_replace('/([\x{0BBE}-\x{0BCC}])\x{0BCD}/u', '$1', $clean);

        // 2. Split on punctuation / whitespace
        preg_match_all('/[\p{L}\p{M}\p{N}]{2,}/u', $clean, $matches);
        $rawTokens = $matches[0] ?? [];

        $keywords = [];
        foreach ($rawTokens as $token) {
            $token = trim($token);
            if (mb_strlen($token, 'UTF-8') < 2) {
                continue;
            }

            // Skip stop words
            if (in_array($token, self::TAMIL_STOP_WORDS, true) ||
                in_array($token, self::ENGLISH_STOP_WORDS, true) ||
                in_array($token, self::SINHALA_STOP_WORDS, true)) {
                continue;
            }

            $stem = $this->normalizeToken($token);
            if (!empty($stem) && !in_array($stem, self::TAMIL_STOP_WORDS, true)) {
                $keywords[] = $stem;
            }
        }

        return array_values(array_unique($keywords));
    }

    /**
     * Stem a single token (Tamil, Sinhala, English) without altering phonetic spelling.
     * Ideal for grounding post-checks and root matching.
     *
     * @param string $token
     * @return string
     */
    public function stemToken(string $token): string
    {
        $token = trim($token);
        if (empty($token)) {
            return '';
        }

        // Detect Tamil script
        if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $token)) {
            return $this->stemTamil($token);
        }

        // Detect Sinhala script
        if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $token)) {
            return $this->stemSinhala($token);
        }

        // English / Latin script
        return $this->stemEnglish($token);
    }

    /**
     * Normalize a single token: strip common grammatical inflections & case markers,
     * and apply phonetic/spelling equivalences.
     *
     * @param string $token
     * @return string
     */
    public function normalizeToken(string $token): string
    {
        $token = trim($token);
        if (empty($token)) {
            return '';
        }

        // Detect Tamil script
        if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $token)) {
            $token = $this->stemTamil($token);
            $token = $this->normalizeTamilSpelling($token);
            return $token;
        }

        // Detect Sinhala script
        if (preg_match('/[\x{0D80}-\x{0DFF}]/u', $token)) {
            $token = $this->stemSinhala($token);
            return $token;
        }

        // English / Latin script
        return $this->stemEnglish($token);
    }

    /**
     * Strip common Tamil noun and verb suffixes (case markers, plurals, clitics).
     */
    public function stemTamil(string $word): string
    {
        // Order suffixes from longest to shortest to peel outer inflection layers
        $suffixes = [
            'வாத்தியத்தை' => 'வாத்தியம்',
            'வாத்தியங்கள்' => 'வாத்தியம்',
            'வாத்தியத்தின்' => 'வாத்தியம்',
            'வாத்தியத்திற்கு' => 'வாத்தியம்',
            'வாத்தியம்' => 'வாத்தியம்',
            'பயன்படுத்தப்படுகின்றன' => 'பயன்படு',
            'பயன்படுத்தப்படுகிறது' => 'பயன்படு',
            'பயன்படுத்தப்படும்' => 'பயன்படு',
            'பயன்படுகிறது' => 'பயன்படு',
            'பயன்படும்' => 'பயன்படு',
            'செய்யப் பயன்படும்' => 'செய்',
            'செய்யப்படும்' => 'செய்',
            'செய்யப்' => 'செய்',
            'செய்ய' => 'செய்',
            'செய்யும்' => 'செய்',
            'செய்வதற்கு' => 'செய்',
            'செய்த' => 'செய்',
            'உதவும்' => 'பயன்படு',
            'உதவ' => 'பயன்படு',
            'தவிலின்' => 'தவில்',
            'தவிலுக்கு' => 'தவில்',
            'தவிலில்' => 'தவில்',
            'மரத்தின்' => 'மரம்',
            'மரத்திற்கு' => 'மரம்',
            'மரத்தில்' => 'மரம்',
            'மரங்கள்' => 'மரம்',
            'மரத்தை' => 'மரம்',
            'மரப்' => 'மரம்',
            'நாட்டியத்திற்கு' => 'நாட்டியம்',
            'நாட்டியத்தில்' => 'நாட்டியம்',
            'நாட்டியத்தின்' => 'நாட்டியம்',
            'நாட்டியம்' => 'நாட்டியம்',
            'இசைக்கப்படுகிறது' => 'இசை',
            'இசைக்கப்படும்' => 'இசை',
            'வாசிக்கப்படுகிறது' => 'வாசி',
            'வாசிக்கப்படும்' => 'வாசி',
        ];

        foreach ($suffixes as $suffix => $replacement) {
            if ($word === $suffix) {
                return $replacement;
            }
        }

        // Regex patterns for generic Tamil case endings and verb inflections
        $regexSuffixes = [
            '/(ப்படுகின்றன|ப்படுகின்றது|ப்படுகிறது|ப்பட்டது|ப்படுபவை|க்கப்படுகின்றன|க்கப்படுகிறது|க்கப்பட்டது|க்கப்படும்|படுகிறது|படும்)$/u' => '',
            '/(த்திற்கு|த்தினால்|த்திற்குரிய|த்திலிருந்து|த்திலுள்ள)$/u' => 'ம்',
            '/(ங்களில்|ங்களை|ங்களின்|ங்களுக்கு|ங்கள்)$/u' => 'ம்',
            '/(க்களில்|க்களை|க்களின்|க்களுக்கு|க்கள்)$/u' => '',
            '/(த்திற்கு|த்தோடு|த்துடன்|த்தினால்|த்திலே)$/u' => 'ம்',
            '/(த்தைய்|த்தை|த்தன்|த்தில்)$/u' => 'ம்',
            '/(உடைய|இன்|இல்|ஆல்|ஐ|கு|க்கு|உக்கு|ஆக|ஆன|வது|தான்|கூட)$/u' => '',
        ];

        foreach ($regexSuffixes as $pattern => $replacement) {
            $stripped = preg_replace($pattern, $replacement, $word);
            if ($stripped !== null && $stripped !== $word && mb_strlen($stripped, 'UTF-8') >= 3) {
                return $stripped;
            }
        }

        return $word;
    }

    /**
     * Normalize spelling variants and known OCR character confusions.
     * Configurable letter equivalences:
     * - மங்கள <-> மங்கல (ள <-> ல)
     * - தவில் <-> தரில் (வி <-> ரி in OCR fonts)
     * - ண <-> ன
     * - ர <-> ற
     */
    public function normalizeTamilSpelling(string $word): string
    {
        // 1. Equate ள -> ல (so மங்கள matches மங்கல)
        $normalized = str_replace('ள', 'ல', $word);

        // 2. Equate ன -> ண
        $normalized = str_replace('ன', 'ண', $normalized);

        // 3. Equate ற -> ர
        $normalized = str_replace('ற', 'ர', $normalized);

        // 4. OCR confusion: 'ரி' (ri) and 'வி' (vi)
        // In Tamil scanned documents, 'வி' is frequently recognized by OCR as 'ரி' (or 'ர').
        // Normalize 'ரி' -> 'வி'
        $normalized = str_replace('ரி', 'வி', $normalized);

        // 5. OCR trailing consonants (e.g. மரப் -> மரம்)
        if (str_ends_with($normalized, 'மரப்')) {
            $normalized = mb_substr($normalized, 0, -1, 'UTF-8') . 'ம்';
        }

        return $normalized;
    }

    /**
     * Stem Sinhala words by stripping common grammatical endings.
     */
    protected function stemSinhala(string $word): string
    {
        $suffixes = [
            '/(ගෙන්|ගේ|ට|හි|වල්|යන්|වරු|ද|ම)$/u' => '',
        ];

        foreach ($suffixes as $pattern => $rep) {
            $stripped = preg_replace($pattern, $rep, $word);
            if ($stripped !== null && $stripped !== $word && mb_strlen($stripped, 'UTF-8') >= 3) {
                return $stripped;
            }
        }

        return $word;
    }

    /**
     * Stem English words.
     */
    protected function stemEnglish(string $word): string
    {
        $word = preg_replace('/(ing|tion|ment|ness|less|ful|ed|es|s)$/i', '', $word);
        return $word ?: '';
    }

    /**
     * Compute keyword overlap between query keywords and candidate chunk text.
     * Returns a float between 0.0 and 1.0.
     *
     * @param array<string> $queryKeywords
     * @param string $chunkContent
     * @return float
     */
    public function computeOverlapScore(array $queryKeywords, string $chunkContent): float
    {
        if (empty($queryKeywords)) {
            return 0.0;
        }

        $chunkKeywords = $this->extractKeywords($chunkContent);
        if (empty($chunkKeywords)) {
            return 0.0;
        }

        $chunkNormalizedText = implode(' ', $chunkKeywords);

        $matchedCount = 0;
        foreach ($queryKeywords as $qk) {
            $found = false;
            foreach ($chunkKeywords as $ck) {
                if ($qk === $ck) {
                    $found = true;
                    break;
                }
                // Prefix stem match for agglutinative words (min 3 chars)
                $minLen = min(mb_strlen($qk, 'UTF-8'), mb_strlen($ck, 'UTF-8'));
                if ($minLen >= 3) {
                    $prefixQ = mb_substr($qk, 0, $minLen, 'UTF-8');
                    $prefixC = mb_substr($ck, 0, $minLen, 'UTF-8');
                    if ($prefixQ === $prefixC) {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found || mb_strpos($chunkNormalizedText, $qk, 0, 'UTF-8') !== false) {
                $matchedCount++;
            }
        }

        return (float) ($matchedCount / count($queryKeywords));
    }

    /**
     * Alias for computeOverlapScore allowing ($chunkContent, $queryKeywords) signature.
     *
     * @param string $chunkContent
     * @param array<string> $queryKeywords
     * @return float
     */
    public function keywordOverlapScore(string $chunkContent, array $queryKeywords): float
    {
        return $this->computeOverlapScore($queryKeywords, $chunkContent);
    }
}
