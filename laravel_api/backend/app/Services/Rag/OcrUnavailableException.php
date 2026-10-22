<?php

namespace App\Services\Rag;

use RuntimeException;

class OcrUnavailableException extends RuntimeException
{
    protected ?int $httpStatus;
    protected ?string $googleStatus;
    protected ?string $shortMessage;

    public function __construct(
        string $message = 'OCR temporarily unavailable, retry later',
        ?int $httpStatus = null,
        ?string $googleStatus = null,
        ?string $shortMessage = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpStatus = $httpStatus;
        $this->googleStatus = $googleStatus;
        $this->shortMessage = $shortMessage;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getGoogleStatus(): ?string
    {
        return $this->googleStatus;
    }

    public function getShortMessage(): ?string
    {
        return $this->shortMessage;
    }

    public function getFormattedRagError(): string
    {
        if ($this->httpStatus) {
            $desc = match ($this->httpStatus) {
                503 => 'overloaded',
                429 => 'rate limit / quota exceeded',
                404 => 'model not found',
                400 => 'bad request',
                500 => 'internal server error',
                502 => 'bad gateway',
                504 => 'gateway timeout',
                default => 'unavailable'
            };
            return "OCR failed: {$this->httpStatus} {$desc}";
        }

        return $this->getMessage();
    }
}
