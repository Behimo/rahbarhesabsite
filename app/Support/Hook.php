<?php

namespace App\Support;

class Hook
{
    private static array $actions = [];

    private static array $filters = [];

    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][$priority][] = $callback;
    }

    public static function doAction(string $hook, mixed ...$args): void
    {
        if (! isset(self::$actions[$hook])) {
            return;
        }

        ksort(self::$actions[$hook]);

        foreach (self::$actions[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
    }

    public static function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (! isset(self::$filters[$hook])) {
            return $value;
        }

        ksort(self::$filters[$hook]);

        foreach (self::$filters[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }
}
