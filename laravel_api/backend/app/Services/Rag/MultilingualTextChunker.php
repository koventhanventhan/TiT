<?php

namespace App\Services\Rag;

class MultilingualTextChunker
{
    protected int $targetWords;
    protected int $overlapWords;

    public function __construct(?int $targetWords = null, ?int $overlapWords = null)
    {
        $this->targetWords = $targetWords ?? (int) config('rag.chunking.target_word_count', 300);
        $this->overlapWords = $overlapWords ?? (int) config('rag.chunking.overlap_words', 40);
    }

    /**
     * Chunk extracted page text preserving sentence integrity, paragraphs,
     * and Tamil, Sinhala, and English words.
     *
     * @param array<int, array{page_number: int, text: string, used_ocr?: bool}> $pages
     * @param string|null $materialType
     * @return array<int, array{page_number: int, content: string, word_count: int, chunk_type?: string, question_number?: string, is_ocr?: bool}>
     */
    public function chunkPages(array $pages, ?string $materialType = null): array
    {
        $chunks = [];
        $isNotes = ($materialType === 'notes');

        foreach ($pages as $page) {
            $pageNumber = $page['page_number'] ?? 1;
            $text = trim($page['text'] ?? '');
            $usedOcr = !empty($page['used_ocr']);

            if (empty($text)) {
                continue;
            }

            // For non-notes materials (or auto-detected question papers), check for numbered-question pattern
            $pageChunks = null;
            if (!$isNotes) {
                $pageChunks = $this->chunkQuestionPage($text, $pageNumber);
            }

            // Fallback to generic semantic chunking if not a question page or if material is notes
            if (empty($pageChunks)) {
                $pageChunks = $this->chunkSinglePage($text, $pageNumber);
                foreach ($pageChunks as &$c) {
                    $c['chunk_type'] = 'page';
                    $c['question_number'] = null;
                }
                unset($c);
            }

            foreach ($pageChunks as $chunk) {
                $chunk['is_ocr'] = $usedOcr;
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    /**
     * Chunk a page containing numbered examination questions into groups of 3 to 5 questions
     * with small overlap and question_number range in metadata (e.g. "1-4", "4-7", chunk_type: "qa").
     *
     * @param string $text
     * @param int $pageNumber
     * @param int $groupSize
     * @param int $overlap
     * @return array<int, array{page_number: int, content: string, word_count: int, chunk_type: string, question_number: string}>|null
     */
    public function chunkQuestionPage(string $text, int $pageNumber, int $groupSize = 4, int $overlap = 1): ?array
    {
        $cleanText = trim($text);
        if (empty($cleanText)) {
            return null;
        }

        $lines = preg_split('/\r?\n/u', $cleanText);
        $sections = [];
        $currentSection = [
            'header' => [],
            'questions' => [],
        ];
        $currentQ = null;
        $prevNum = -1;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            // Match numbered questions: e.g. "1.", "1)", "(1)", "1 -", "வினா 1", "Q1", "Question 1"
            if (preg_match('/^(?:(?:\(?(\d{1,3})\s*[\.\)\-:]|\((\d{1,3})\))|(?:வினா|Question|Q|பிரச்னை)\s*(\d{1,3})[\.\:\)]?)\s*(.*)$/ui', $trimmedLine, $m)) {
                $num = 0;
                for ($i = 1; $i <= 3; $i++) {
                    if (!empty($m[$i])) {
                        $num = (int) $m[$i];
                        break;
                    }
                }

                // If question number resets significantly (e.g. 20 -> 1) or a major section header preceded it, start a new section
                if ($num <= $prevNum && $prevNum > 0 && ($prevNum - $num) >= 2) {
                    if ($currentQ !== null) {
                        $currentSection['questions'][] = $currentQ;
                        $currentQ = null;
                    }
                    if (!empty($currentSection['questions'])) {
                        $sections[] = $currentSection;
                    }
                    $currentSection = [
                        'header' => [],
                        'questions' => [],
                    ];
                }

                if ($currentQ !== null) {
                    $currentSection['questions'][] = $currentQ;
                }

                $currentQ = [
                    'number' => $num,
                    'lines' => [$line],
                ];
                $prevNum = $num;
            } elseif ($currentQ !== null) {
                // If line looks like a major section header (e.g. "பகுதி II", "Part B", "Section 2")
                if (preg_match('/^(?:பகுதி|Part|Section|பிரிவு)\s*[I|V|X|\d]+/ui', $trimmedLine)) {
                    $currentSection['questions'][] = $currentQ;
                    $currentQ = null;
                    if (!empty($currentSection['questions'])) {
                        $sections[] = $currentSection;
                    }
                    $currentSection = [
                        'header' => [$line],
                        'questions' => [],
                    ];
                    $prevNum = -1;
                } else {
                    $currentQ['lines'][] = $line;
                }
            } else {
                $currentSection['header'][] = $line;
            }
        }

        if ($currentQ !== null) {
            $currentSection['questions'][] = $currentQ;
        }
        if (!empty($currentSection['questions'])) {
            $sections[] = $currentSection;
        }

        // Count total questions across all sections
        $totalQuestions = 0;
        foreach ($sections as $sec) {
            $totalQuestions += count($sec['questions']);
        }

        // If fewer than 3 total questions found, do not treat as question paper
        if ($totalQuestions < 3) {
            return null;
        }

        $allChunks = [];

        foreach ($sections as $sec) {
            $questions = $sec['questions'];
            $header = trim(implode("\n", $sec['header']));
            $n = count($questions);

            if ($n === 0) {
                continue;
            }

            if ($n <= 5) {
                // All questions fit in one chunk
                $qStart = $questions[0]['number'];
                $qEnd = $questions[$n - 1]['number'];
                $content = !empty($header) ? ($header . "\n\n") : '';
                foreach ($questions as $q) {
                    $content .= implode("\n", $q['lines']) . "\n\n";
                }
                $content = trim($content);
                $allChunks[] = [
                    'page_number' => $pageNumber,
                    'content' => $content,
                    'word_count' => count(preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: []),
                    'chunk_type' => 'qa',
                    'question_number' => ($qStart === $qEnd) ? (string) $qStart : "{$qStart}-{$qEnd}",
                ];
                continue;
            }

            // Sliding window: size 4, overlap 1 (step = 3)
            $step = max(1, $groupSize - $overlap);
            $i = 0;
            while ($i < $n) {
                $slice = array_slice($questions, $i, $groupSize);
                // If tail slice is too small (< 3) and not the only chunk, expand backward
                if (count($slice) < 3 && !empty($allChunks)) {
                    $take = min(4, $n);
                    $slice = array_slice($questions, $n - $take, $take);
                    $qStart = $slice[0]['number'];
                    $qEnd = $slice[count($slice) - 1]['number'];
                    $content = (!empty($header) && $i === 0) ? ($header . "\n\n") : '';
                    foreach ($slice as $q) {
                        $content .= implode("\n", $q['lines']) . "\n\n";
                    }
                    $content = trim($content);
                    $allChunks[] = [
                        'page_number' => $pageNumber,
                        'content' => $content,
                        'word_count' => count(preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: []),
                        'chunk_type' => 'qa',
                        'question_number' => ($qStart === $qEnd) ? (string) $qStart : "{$qStart}-{$qEnd}",
                    ];
                    break;
                }

                $qStart = $slice[0]['number'];
                $qEnd = $slice[count($slice) - 1]['number'];
                $content = (!empty($header) && $i === 0) ? ($header . "\n\n") : '';
                foreach ($slice as $q) {
                    $content .= implode("\n", $q['lines']) . "\n\n";
                }
                $content = trim($content);

                $allChunks[] = [
                    'page_number' => $pageNumber,
                    'content' => $content,
                    'word_count' => count(preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: []),
                    'chunk_type' => 'qa',
                    'question_number' => ($qStart === $qEnd) ? (string) $qStart : "{$qStart}-{$qEnd}",
                ];

                $i += $step;
                if ($i + $groupSize > $n && ($n - $i) < 2) {
                    break;
                }
            }
        }

        return $allChunks;
    }

    /**
     * Split a single page into sentence-packed, paragraph-aware overlapping chunks.
     *
     * @param string $text
     * @param int $pageNumber
     * @return array<int, array{page_number: int, content: string, word_count: int}>
     */
    public function chunkSinglePage(string $text, int $pageNumber): array
    {
        $cleanText = trim($text);
        if (empty($cleanText)) {
            return [];
        }

        $sentences = $this->extractSentencesWithParagraphs($cleanText);
        if (empty($sentences)) {
            return [];
        }

        $totalWords = array_sum(array_column($sentences, 'word_count'));

        // If the entire text fits within targetWords, return as single chunk without splitting
        if ($totalWords <= $this->targetWords) {
            return [
                [
                    'page_number' => $pageNumber,
                    'content' => $this->joinSentences($sentences),
                    'word_count' => $totalWords,
                ],
            ];
        }

        $chunks = [];
        $numSentences = count($sentences);
        $startIndex = 0;

        while ($startIndex < $numSentences) {
            $currentWords = 0;
            $chunkSentences = [];
            $endIndex = $startIndex;

            while ($endIndex < $numSentences) {
                $sentenceWordCount = $sentences[$endIndex]['word_count'];
                if (!empty($chunkSentences) && ($currentWords + $sentenceWordCount > $this->targetWords)) {
                    // Check if remaining words in the whole page are small (<= overlapWords).
                    // If so, absorb them into this chunk to prevent an orphan trailing chunk.
                    $remainingWords = 0;
                    for ($k = $endIndex; $k < $numSentences; $k++) {
                        $remainingWords += $sentences[$k]['word_count'];
                    }
                    if ($remainingWords <= $this->overlapWords) {
                        for ($k = $endIndex; $k < $numSentences; $k++) {
                            $chunkSentences[] = $sentences[$k];
                            $currentWords += $sentences[$k]['word_count'];
                        }
                        $endIndex = $numSentences;
                    }
                    break;
                }
                $chunkSentences[] = $sentences[$endIndex];
                $currentWords += $sentenceWordCount;
                $endIndex++;
            }

            // Fallback for single oversized sentence
            if (empty($chunkSentences)) {
                $chunkSentences[] = $sentences[$startIndex];
                $currentWords = $sentences[$startIndex]['word_count'];
                $endIndex = $startIndex + 1;
            }

            $chunks[] = [
                'page_number' => $pageNumber,
                'content' => $this->joinSentences($chunkSentences),
                'word_count' => $currentWords,
            ];

            // If the chunk reached the end of the page, stop. No orphan chunk is created.
            if ($endIndex >= $numSentences) {
                break;
            }

            // Compute overlap by looking backward from $endIndex
            $overlapCount = 0;
            $nextStart = $endIndex;

            while ($nextStart > $startIndex + 1) {
                $candidateCount = $sentences[$nextStart - 1]['word_count'];
                if (($overlapCount + $candidateCount > $this->overlapWords) && $overlapCount > 0) {
                    break;
                }
                $overlapCount += $candidateCount;
                $nextStart--;
            }

            // Guarantee strictly forward progress
            $startIndex = max($startIndex + 1, $nextStart);
        }

        return $chunks;
    }

    /**
     * Extract whole sentences while preserving paragraph breaks.
     *
     * @param string $text
     * @return array<int, array{text: string, is_paragraph_start: bool, word_count: int}>
     */
    protected function extractSentencesWithParagraphs(string $text): array
    {
        // Split text into paragraphs by double newlines
        $paragraphs = preg_split('/(\r?\n){2,}/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = [];

        foreach ($paragraphs as $paragraphIndex => $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                continue;
            }

            // Split paragraph into sentences by sentence terminators (. ! ? । ॥ or line breaks)
            $rawSentences = preg_split('/(?<=[.!?।॥\r\n])\s+/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($rawSentences as $sentenceIndex => $rawSentence) {
                $trimmed = trim($rawSentence);
                if (empty($trimmed)) {
                    continue;
                }

                $wordCount = count(preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY));

                // If a single sentence exceeds targetWords, break it on whitespace
                if ($wordCount > $this->targetWords) {
                    $subWords = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
                    $slices = array_chunk($subWords, $this->targetWords);
                    foreach ($slices as $sliceIndex => $slice) {
                        $sentences[] = [
                            'text' => implode(' ', $slice),
                            'is_paragraph_start' => ($sentenceIndex === 0 && $sliceIndex === 0),
                            'word_count' => count($slice),
                        ];
                    }
                } else {
                    $sentences[] = [
                        'text' => $trimmed,
                        'is_paragraph_start' => ($sentenceIndex === 0),
                        'word_count' => $wordCount,
                    ];
                }
            }
        }

        return $sentences;
    }

    /**
     * Reassemble sentences maintaining paragraph separation.
     *
     * @param array<int, array{text: string, is_paragraph_start: bool, word_count: int}> $sentences
     * @return string
     */
    protected function joinSentences(array $sentences): string
    {
        $output = '';

        foreach ($sentences as $index => $sentence) {
            if ($index === 0) {
                $output .= $sentence['text'];
            } elseif ($sentence['is_paragraph_start']) {
                $output .= "\n\n" . $sentence['text'];
            } else {
                $output .= ' ' . $sentence['text'];
            }
        }

        return trim($output);
    }
}