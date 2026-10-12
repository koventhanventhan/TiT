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
     * @param array<int, array{page_number: int, text: string}> $pages
     * @return array<int, array{page_number: int, content: string, word_count: int}>
     */
    public function chunkPages(array $pages): array
    {
        $chunks = [];

        foreach ($pages as $page) {
            $pageNumber = $page['page_number'] ?? 1;
            $text = trim($page['text'] ?? '');

            if (empty($text)) {
                continue;
            }

            $pageChunks = $this->chunkSinglePage($text, $pageNumber);
            foreach ($pageChunks as $chunk) {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
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
