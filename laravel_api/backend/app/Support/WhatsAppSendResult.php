<?php

namespace App\Support;

final class WhatsAppSendResult
{
    public const STATUS_SENT = 'sent';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        public readonly string $status,
        public readonly ?string $reason = null,
        public readonly ?string $messageId = null,
        public readonly ?string $template = null,
        public readonly array $response = [],
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function sent(?string $messageId = null, ?string $template = null, array $response = []): self
    {
        return new self(
            status: self::STATUS_SENT,
            reason: null,
            messageId: $messageId,
            template: $template,
            response: $response,
        );
    }

    public static function skipped(string $reason = 'unconfigured'): self
    {
        return new self(
            status: self::STATUS_SKIPPED,
            reason: $reason,
        );
    }

    public static function failed(string $reason, ?string $template = null): self
    {
        return new self(
            status: self::STATUS_FAILED,
            reason: Redact::sanitizeText($reason),
            template: $template,
        );
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
