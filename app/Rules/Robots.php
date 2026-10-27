<?php

namespace App\Rules;

use App\Support\RobotsDirectives;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Robots implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! RobotsDirectives::valid($value)) {
            $fail('دستور ربات جستجو معتبر نیست.');
        }
    }
}
