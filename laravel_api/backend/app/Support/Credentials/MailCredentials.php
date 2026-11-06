<?php

namespace App\Support\Credentials;

use App\Support\Redact;

final class MailCredentials
{
    public function __construct(
        public readonly int $instituteId,
        public readonly string $mailer, // smtp | log | array | failover | etc.
        public readonly ?string $host,
        public readonly ?int $port,
        public readonly ?string $encryption, // tls | ssl | null
        public readonly ?string $username,
        public readonly ?string $password,
        public readonly string $fromAddress,
        public readonly string $fromName,
        public readonly ?string $replyToAddress = null,
        public readonly ?string $replyToName = null,
        public readonly string $source = 'db', // db | env
        public readonly string $status = 'configured',
    ) {}

    public function maskedPassword(): string
    {
        return Redact::maskValue($this->password);
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'instituteId' => $this->instituteId,
            'mailer' => $this->mailer,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'password' => $this->maskedPassword(),
            'fromAddress' => $this->fromAddress,
            'fromName' => $this->fromName,
            'replyToAddress' => $this->replyToAddress,
            'replyToName' => $this->replyToName,
            'source' => $this->source,
            'status' => $this->status,
        ];
    }
}
