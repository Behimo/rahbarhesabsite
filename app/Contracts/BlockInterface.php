<?php

namespace App\Contracts;

interface BlockInterface
{
    public function type(): string;

    public function label(): string;

    public function schema(): array;

    public function render(array $settings): string;

    public function assets(): array;
}
