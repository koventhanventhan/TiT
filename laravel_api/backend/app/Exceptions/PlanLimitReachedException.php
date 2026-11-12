<?php

namespace App\Exceptions;

use RuntimeException;

class PlanLimitReachedException extends RuntimeException
{
    protected ?int $limit;
    protected ?int $currentCount;
    protected int $adding;
    protected string $type;

    public function __construct(
        string $message = "Plan limit reached.",
        ?int $limit = null,
        ?int $currentCount = null,
        int $adding = 1,
        string $type = 'student',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->limit = $limit;
        $this->currentCount = $currentCount;
        $this->adding = $adding;
        $this->type = $type;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getCurrentCount(): ?int
    {
        return $this->currentCount;
    }

    public function getAdding(): int
    {
        return $this->adding;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'plan_limit_reached',
                'limit' => $this->limit,
                'current' => $this->currentCount,
                'type' => $this->type,
            ], 422);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $this->getMessage());
    }
}

