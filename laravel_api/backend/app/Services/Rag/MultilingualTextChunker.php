<?php

namespace App\Services\Rag;

class MultilingualTextChunker
{
    protected int $targetWords;
    protected int $overlapWords;

    public function __construct(?int $targetWords = null, ?int $overlapWords = null)
    {
        $this->targetWords = $targetWords ?? (int) config('rag.chunking.target_word_count', 400);
        $this->overlapWords = $overlapWords ?? (int) config('rag.chunking.overlap_words', 50);
    }

    /**
     * Chunk extracted page text preserving Tamil, Sinhala, and English word boundaries.
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
     * Split a single page into overlapping word-bounded chunks.
     *
     * @param string $text
     * @param int $pageNumber
     * @return array<int, array{page_number: int, content: string, word_count: int}>
     */
    public function chunkSinglePage(string $text, int $pageNumber): array
    {
        // Normalize whitespace and newlines
        $cleanedText = preg_replace('/[ \t]+/u', ' ', $text);
        $cleanedText = preg_replace('/(\r?\n){3,}/u', "\n\n", $cleanedText);
        $cleanedText = trim($cleanedText);

        if (empty($cleanedText)) {
            return [];
        }

        // Split into words preserving Unicode letters (Tamil, Sinhala, English, symbols)
        $words = preg_split('/[\s\p{Z}]+/u', $cleanedText, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return [];
        }

        $totalWords = count($words);

        // If the entire page fits within target words, keep as single chunk
        if ($totalWords <= $this->targetWords) {
            return [
                [
                    'page_number' => $pageNumber,
                    'content' => implode(' ', $words),
                    'word_count' => $totalWords,
                ],
            ];
        }

        $chunks = [];
        $step = max(1, $this->targetWords - $this->overlapWords);
        $start = 0;

        while ($start < $totalWords) {
            $slice = array_slice($words, $start, $this->targetWords);
            $wordCount = count($slice);

            if ($wordCount === 0) {
                break;
            }

            // If the remaining words are fewer than the overlap size and we already have chunks,
            // merge them into the last chunk instead of creating an orphan chunk
            if ($start > 0 && $wordCount < ($this->overlapWords / 2) && !empty($chunks)) {
                $lastIndex = count($chunks) - 1;
                $lastWords = explode(' ', $chunks[$lastIndex]['content']);
                $mergedWords = array_unique(array_merge($lastWords, $slice));
                $chunks[$lastIndex]['content'] = implode(' ', $mergedWords);
                $chunks[$lastIndex]['word_count'] = count($mergedWords);
                break;
            }

            $chunks[] = [
                'page_number' => $pageNumber,
                'content' => implode(' ', $slice),
                'word_count' => $wordCount,
            ];

            $start += $step;
        }

        return $chunks;
    }
}
