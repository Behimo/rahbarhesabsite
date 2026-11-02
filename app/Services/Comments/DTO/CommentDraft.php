<?php

namespace App\Services\Comments\DTO;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class CommentDraft
{
    public function __construct(
        public readonly Model $commentable,
        public readonly string $body,
        public readonly ?User $user,
        public readonly ?string $authorName,
        public readonly ?string $authorEmail,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly ?int $parentId,
        public readonly ?string $honeypot = null,
    ) {}
}
