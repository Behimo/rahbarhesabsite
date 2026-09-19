<?php

namespace App\Services;

class PageRenderContext
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @param  array<string, mixed>  $data */
    public function set(array $data): void
    {
        $this->data = $data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }
}
