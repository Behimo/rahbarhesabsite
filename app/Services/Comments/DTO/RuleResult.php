<?php

namespace App\Services\Comments\DTO;

final class RuleResult
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public readonly ?string $action = null,
        public readonly int $score = 0,
        public readonly array $reasons = [],
        public readonly ?string $message = null,
        public readonly bool $stop = false,
    ) {}

    public static function pass(int $score = 0, array $reasons = []): self
    {
        return new self(score: $score, reasons: $reasons);
    }

    public static function reject(string $reason, ?string $message = null, int $score = 100): self
    {
        return new self(
            action: GuardVerdict::ACTION_REJECT,
            score: $score,
            reasons: [$reason],
            message: $message,
            stop: true,
        );
    }

    public static function spam(string $reason, int $score = 80, ?string $message = null): self
    {
        return new self(
            action: GuardVerdict::ACTION_SPAM,
            score: $score,
            reasons: [$reason],
            message: $message,
            stop: true,
        );
    }

    public static function pending(string $reason, int $score = 40, ?string $message = null): self
    {
        return new self(
            action: GuardVerdict::ACTION_PENDING,
            score: $score,
            reasons: [$reason],
            message: $message,
        );
    }
}
