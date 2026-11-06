<?php

namespace App\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;

final class InstitutePendingMail
{
    private mixed $ccUsers = null;
    private mixed $bccUsers = null;

    public function __construct(
        private readonly mixed $toUsers,
        private readonly ?int $instituteId = null,
    ) {}

    public function cc(mixed $users): self
    {
        $this->ccUsers = $users;
        return $this;
    }

    public function bcc(mixed $users): self
    {
        $this->bccUsers = $users;
        return $this;
    }

    /**
     * Send (or queue if Mailable implements ShouldQueue) through the institute's resolved mailer.
     * Catches any transport/SMTP error, records sanitised failure on the institute's mail integration row,
     * and returns false instead of throwing into business flows.
     */
    public function send(Mailable $mailable): bool
    {
        try {
            return $this->dispatchMail($mailable, forceQueue: false);
        } catch (\Throwable $e) {
            InstituteMail::handleFailure($this->instituteId, $e);
            return false;
        }
    }

    /**
     * Same as send(), but rethrows a sanitised RuntimeException on failure (used by explicit diagnostic/test actions).
     */
    public function sendOrThrow(Mailable $mailable): void
    {
        try {
            $sent = $this->dispatchMail($mailable, forceQueue: false);
            if (!$sent) {
                throw new \RuntimeException(InstituteMail::getLastError() ?: 'Mail integration is not configured for this institute.');
            }
        } catch (\Throwable $e) {
            $sanitised = InstituteMail::handleFailure($this->instituteId, $e);
            throw new \RuntimeException($sanitised);
        }
    }

    /**
     * Explicitly queue a mailable with tenant metadata embedded in the queue payload.
     */
    public function queue(Mailable $mailable, ?string $queue = null): bool
    {
        try {
            return $this->dispatchMail($mailable, forceQueue: true, queue: $queue);
        } catch (\Throwable $e) {
            InstituteMail::handleFailure($this->instituteId, $e);
            return false;
        }
    }

    private function dispatchMail(Mailable $mailable, bool $forceQueue = false, ?string $queue = null): bool
    {
        InstituteMail::clearLastError();

        $configured = InstituteMail::configureMailer($this->instituteId);
        $mailerName = $configured['mailer'];
        $creds = $configured['creds'];
        $resolvedId = $configured['institute_id'];

        if ($mailerName === null || $creds === null) {
            InstituteMail::setUnconfiguredReason("Mail is not configured for institute #{$resolvedId}.");
            return false;
        }

        InstituteMail::applyEnvelopeDefaults($mailable, $creds, $mailerName);

        $previousDispatchId = InstituteMail::$dispatchingInstituteId;
        InstituteMail::$dispatchingInstituteId = $resolvedId;

        try {
            // integration-ok: canonical single entry point into Laravel Mail facade
            $pending = Mail::mailer($mailerName)->to($this->toUsers);

            if ($this->ccUsers !== null) {
                $pending->cc($this->ccUsers);
            }
            if ($this->bccUsers !== null) {
                $pending->bcc($this->bccUsers);
            }

            if ($forceQueue) {
                if ($queue !== null && method_exists($mailable, 'onQueue')) {
                    $mailable->onQueue($queue);
                }
                $pending->queue($mailable);
                return true;
            }

            $pending->send($mailable);

            // Mark DB row verified only when sent synchronously against real transport (not queued, not MailFake)
            $isFake = Mail::getFacadeRoot() instanceof MailFake;
            if (!$isFake && !($mailable instanceof ShouldQueue) && $creds->source === 'db') {
                InstituteMail::markVerifiedIfDbRow($resolvedId);
            }

            return true;
        } finally {
            InstituteMail::$dispatchingInstituteId = $previousDispatchId;
        }
    }
}
