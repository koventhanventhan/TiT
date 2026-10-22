<?php

namespace App\Services\Rag;

use RuntimeException;

class GeminiSafetyBlockedException extends RuntimeException
{
    protected ?string $blockReason;
    protected ?string $finishReason;
    protected array $safetyRatings;

    public function __construct(
        string $message = 'Content was blocked by safety filters.',
        ?string $blockReason = null,
        ?string $finishReason = null,
        array $safetyRatings = []
    ) {
        parent::__construct($message);
        $this->blockReason = $blockReason;
        $this->finishReason = $finishReason;
        $this->safetyRatings = $safetyRatings;
    }

    public function getBlockReason(): ?string
    {
        return $this->blockReason;
    }

    public function getFinishReason(): ?string
    {
        return $this->finishReason;
    }

    public function getSafetyRatings(): array
    {
        return $this->safetyRatings;
    }
}
