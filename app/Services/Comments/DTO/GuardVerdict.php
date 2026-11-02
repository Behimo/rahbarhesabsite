<?php

namespace App\Services\Comments\DTO;

final class GuardVerdict
{
    public const ACTION_ALLOW = 'allow';

    public const ACTION_PENDING = 'pending';

    public const ACTION_SPAM = 'spam';

    public const ACTION_REJECT = 'reject';

    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public readonly string $action,
        public readonly int $score = 0,
        public readonly array $reasons = [],
        public readonly ?string $message = null,
    ) {}

    public function isAllow(): bool
    {
        return $this->action === self::ACTION_ALLOW;
    }

    public function isReject(): bool
    {
        return $this->action === self::ACTION_REJECT;
    }

    public function isSpam(): bool
    {
        return $this->action === self::ACTION_SPAM;
    }

    public function isPending(): bool
    {
        return $this->action === self::ACTION_PENDING;
    }
}
