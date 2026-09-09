<?php

namespace App\Contracts;

interface ThemeInterface
{
    public function slug(): string;

    public function name(): string;

    public function version(): string;

    public function manifest(): array;
}
