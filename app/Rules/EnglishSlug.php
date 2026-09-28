<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnglishSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('نامک فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.');

            return;
        }

        if (preg_match('/\A[a-zA-Z0-9_-]+\z/', (string) $value) !== 1) {
            $fail('نامک فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.');
        }
    }
}
