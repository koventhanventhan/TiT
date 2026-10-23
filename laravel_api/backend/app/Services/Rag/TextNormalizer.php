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

    public bool $suffixStrippingEnabled;
    public bool $variantsEnabled;
    public bool $fuzzyMatchingEnabled;
    public int $minFuzzyWordLength;
    public array $variants;

    public function __construct(
        ?bool $suffixStrippingEnabled = null,
        ?bool $variantsEnabled = null,
        ?bool $fuzzyMatchingEnabled = null,
        ?int $minFuzzyWordLength = null,
        ?array $variants = null
    ) {
        $hasConfig = class_exists(\Illuminate\Container\Container::class) && \Illuminate\Container\Container::getInstance() && \Illuminate\Container\Container::getInstance()->bound('config');
        $this->suffixStrippingEnabled = $suffixStrippingEnabled ?? ($hasConfig ? (bool) config('rag.normalizer.suffix_stripping_enabled', true) : true);
        $this->variantsEnabled = $variantsEnabled ?? ($hasConfig ? (bool) config('rag.normalizer.variants_enabled', true) : true);
        $this->fuzzyMatchingEnabled = $fuzzyMatchingEnabled ?? ($hasConfig ? (bool) config('rag.normalizer.fuzzy_matching_enabled', true) : true);
        $this->minFuzzyWordLength = $minFuzzyWordLength ?? ($hasConfig ? (int) config('rag.normalizer.min_fuzzy_word_length', 5) : 5);
        $this->variants = $variants ?? ($hasConfig ? (array) config('rag.normalizer.variants', [
            'மங்கல' => 'மங்கள',
            'மங்கள' => 'மங்கல',
        ]) : [
            'மங்கல' => 'மங்கள',
            'மங்கள' => 'மங்கல',
        ]);
    }

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
     * Stem a single token (Tamil, Sinhala, English).
     *
     * @param string $token
     * @return string
     */
    public function stemToken(string $token): string
    {
        $token = trim($token);
        if (empty($token) || !$this->suffixStrippingEnabled) {
            return $token;
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
     * Normalize a single token: apply suffix stripping if enabled.
     *
     * NOTE: Blanket character equivalences (ள/ல, ன/ண, ற/ர) and OCR substitutions
     * (ரி/வி, மரப்/மரம்) are strictly PROHIBITED to prevent distinct lexical
     * words from being merged (e.g. கல்/கள், அறம்/அரம், மரம்/மறம்).
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

        return $this->stemToken($token);
    }

    /**
     * Strip common Tamil noun and verb suffixes (case markers, plurals, clitics).
     * Guarded by minimum length checks so short distinct root words are never stripped.
     */
    public function stemTamil(string $word): string
    {
        if (!$this->suffixStrippingEnabled) {
            return $word;
        }

        $charLen = mb_strlen($word, 'UTF-8');
        // Do not strip suffixes on short words (< 4 characters) to protect root words like கல், கள், கண், மரம், அறம்
        if ($charLen < 4) {
            return $word;
        }

        // Specific grammatical inflection patterns for common educational nouns & verbs
        $inflections = [
            'வாத்தியத்தை' => 'வாத்தியம்',
            'வாத்தியங்கள்' => 'வாத்தியம்',
            'வாத்தியங்களின்' => 'வாத்தியம்',
            'வாத்தியங்களுக்கு' => 'வாத்தியம்',
            'வாத்தியத்தின்' => 'வாத்தியம்',
            'வாத்தியத்திற்கு' => 'வாத்தியம்',
            'வாத்தியத்தில்' => 'வாத்தியம்',
            'பயன்படுத்தப்படுகின்றன' => 'பயன்படு',
            'பயன்படுத்தப்படுகிறது' => 'பயன்படு',
            'பயன்படுத்தப்படும்' => 'பயன்படு',
            'பயன்படுகிறது' => 'பயன்படு',
            'பயன்படும்' => 'பயன்படு',
            'பயன்பாட்டில்' => 'பயன்படு',
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
            'தவிலை' => 'தவில்',
            'மரத்தின்' => 'மரம்',
            'மரத்திற்கு' => 'மரம்',
            'மரத்தில்' => 'மரம்',
            'மரங்கள்' => 'மரம்',
            'மரங்களை' => 'மரம்',
            'மரங்களின்' => 'மரம்',
            'மரங்களுக்கு' => 'மரம்',
            'மரத்தை' => 'மரம்',
            'நாட்டியத்திற்கு' => 'நாட்டியம்',
            'நாட்டியத்தில்' => 'நாட்டியம்',
            'நாட்டியத்தின்' => 'நாட்டியம்',
            'நாட்டியத்தை' => 'நாட்டியம்',
            'இசைக்கப்படுகிறது' => 'இசை',
            'இசைக்கப்படும்' => 'இசை',
            'வாசிக்கப்படுகிறது' => 'வாசி',
            'வாசிக்கப்படும்' => 'வாசி',
        ];

        if (isset($inflections[$word])) {
            return $inflections[$word];
        }

        // Generic Tamil case markers and plurals
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
     * Stem Sinhala words by stripping common grammatical endings.
     */
    protected function stemSinhala(string $word): string
    {
        if (!$this->suffixStrippingEnabled || mb_strlen($word, 'UTF-8') < 4) {
            return $word;
        }

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
        if (!$this->suffixStrippingEnabled || mb_strlen($word, 'UTF-8') < 4) {
            return $word;
        }

        $word = preg_replace('/(ing|tion|ment|ness|less|ful|ed|es|s)$/i', '', $word);
        return $word ?: '';
    }

    /**
     * Check if two tokens match, considering:
     * 1. Exact equality after stemming
     * 2. Configurable variants mapping (if enabled)
     * 3. Fuzzy matching limited to edit distance <= 1 for words with 5 or more characters (if enabled)
     *
     * @param string $tokenA
     * @param string $tokenB
     * @return bool
     */
    public function tokensMatch(string $tokenA, string $tokenB): bool
    {
        $stemA = $this->stemToken($tokenA);
        $stemB = $this->stemToken($tokenB);

        if ($stemA === $stemB) {
            return true;
        }

        // 1. Configurable variants list
        if ($this->variantsEnabled) {
            $canonicalA = $this->variants[$stemA] ?? $stemA;
            $canonicalB = $this->variants[$stemB] ?? $stemB;
            if ($canonicalA === $stemB || $canonicalB === $stemA || $canonicalA === $canonicalB) {
                return true;
            }
        }

        // 2. Fuzzy matching: strictly limited to edit distance 1 for words of 5 or more characters
        if ($this->fuzzyMatchingEnabled) {
            $lenA = mb_strlen($stemA, 'UTF-8');
            $lenB = mb_strlen($stemB, 'UTF-8');

            if ($lenA >= $this->minFuzzyWordLength && $lenB >= $this->minFuzzyWordLength) {
                if ($this->mbLevenshtein($stemA, $stemB) <= 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Compute true UTF-8 character Levenshtein distance.
     *
     * @param string $str1
     * @param string $str2
     * @return int
     */
    public function mbLevenshtein(string $str1, string $str2): int
    {
        $chars1 = preg_split('//u', $str1, -1, PREG_SPLIT_NO_EMPTY);
        $chars2 = preg_split('//u', $str2, -1, PREG_SPLIT_NO_EMPTY);
        $len1 = count($chars1);
        $len2 = count($chars2);

        if ($len1 === 0) {
            return $len2;
        }
        if ($len2 === 0) {
            return $len1;
        }
        if (abs($len1 - $len2) > 1) {
            return 2; // early exit when difference exceeds 1
        }

        $d = [];
        for ($i = 0; $i <= $len1; $i++) {
            $d[$i][0] = $i;
        }
        for ($j = 0; $j <= $len2; $j++) {
            $d[0][$j] = $j;
        }

        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = ($chars1[$i - 1] === $chars2[$j - 1]) ? 0 : 1;
                $d[$i][$j] = min(
                    $d[$i - 1][$j] + 1,       // deletion
                    $d[$i][$j - 1] + 1,       // insertion
                    $d[$i - 1][$j - 1] + $cost // substitution
                );
            }
        }

        return $d[$len1][$len2];
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

        $matchedCount = 0;
        foreach ($queryKeywords as $qk) {
            $found = false;
            foreach ($chunkKeywords as $ck) {
                if ($this->tokensMatch($qk, $ck)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
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
