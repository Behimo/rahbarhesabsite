<?php

namespace App\Support\Admin;

class FeedbackNotice
{
    public function __construct(
        public readonly string $level,
        public readonly string $message,
    ) {}
}
