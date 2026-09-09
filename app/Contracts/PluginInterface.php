<?php

namespace App\Contracts;

interface PluginInterface
{
    public function slug(): string;

    public function name(): string;

    public function version(): string;

    public function boot(): void;
}
