<?php

namespace App\Support;

/**
 * Prefixes CSS selectors so a stylesheet only matches inside one container.
 * html, body and :root are rewritten onto that container so Elementor resets
 * keep working in the page without touching the rest of the site.
 */
class CssScope
{
    public function scope(string $css, string $scope): string
    {
        $scope = trim($scope);
        if ($scope === '' || trim($css) === '') {
            return '';
        }

        $index = 0;

        return $this->transform($css, $index, strlen($css), $scope);
    }

    private function transform(string $css, int &$index, int $length, string $scope): string
    {
        $output = '';

        while ($index < $length) {
            $this->skipIgnorable($css, $index, $length);
            if ($index >= $length || $css[$index] === '}') {
                break;
            }

            if ($css[$index] === '@') {
                $output .= $this->transformAtRule($css, $index, $length, $scope);

                continue;
            }

            $selector = $this->readUntil($css, $index, $length, '{');
            $this->skipIgnorable($css, $index, $length);
            if ($index >= $length || $css[$index] !== '{') {
                break;
            }

            $block = $this->readBlock($css, $index, $length);
            $scoped = $this->scopeSelectorList($selector, $scope);
            if ($scoped !== '') {
                $output .= $scoped.$block;
            }
        }

        return $output;
    }

    private function transformAtRule(string $css, int &$index, int $length, string $scope): string
    {
        $prelude = $this->readUntil($css, $index, $length, '{;');
        if ($index >= $length) {
            return '';
        }

        if ($css[$index] === ';') {
            $index++;
            $name = $this->atRuleName($prelude);
            if ($name === '@charset' || $name === '@import') {
                return '';
            }

            return trim($prelude).';';
        }

        $block = $this->readBlock($css, $index, $length);
        $name = $this->atRuleName($prelude);
        if ($this->passesThrough($name)) {
            return $prelude.$block;
        }

        $inner = substr($block, 1, -1);
        $innerIndex = 0;

        return $prelude.'{'.$this->transform($inner, $innerIndex, strlen($inner), $scope).'}';
    }

    private function atRuleName(string $prelude): string
    {
        if (! preg_match('/^@[a-zA-Z-]+/', trim($prelude), $matches)) {
            return '';
        }

        return strtolower($matches[0]);
    }

    private function passesThrough(string $name): bool
    {
        return in_array($name, [
            '@font-face',
            '@page',
            '@counter-style',
            '@font-feature-values',
            '@font-palette-values',
            '@property',
            '@namespace',
            '@color-profile',
        ], true) || str_contains($name, 'keyframes');
    }

    private function scopeSelectorList(string $selectors, string $scope): string
    {
        $selectors = $this->stripComments($selectors);
        $scoped = [];

        foreach ($this->splitSelectors($selectors) as $selector) {
            $selector = trim($selector);
            if ($selector === '') {
                continue;
            }
            $scoped[] = $this->scopeSelector($selector, $scope);
        }

        return implode(',', $scoped);
    }

    private function scopeSelector(string $selector, string $scope): string
    {
        if (preg_match('/^(?:from|to|-?\d+(?:\.\d+)?%)$/i', $selector)) {
            return $selector;
        }

        $rewritten = preg_replace('/^(?:html|body|:root)(?![-\w])/i', $scope, $selector, 1, $count);
        if (($count ?? 0) > 0 && is_string($rewritten)) {
            if (str_starts_with($rewritten, $scope.'*')) {
                return $scope.' '.substr($rewritten, strlen($scope));
            }

            return $rewritten;
        }

        $quoted = preg_quote($scope, '/');
        if (preg_match('/^'.$quoted.'(?![-\w])/', $selector)) {
            return $selector;
        }

        return $scope.' '.$selector;
    }

    /**
     * @return list<string>
     */
    private function splitSelectors(string $selectors): array
    {
        $parts = [];
        $buffer = '';
        $length = strlen($selectors);
        $parentheses = 0;
        $brackets = 0;
        $quote = null;

        for ($i = 0; $i < $length; $i++) {
            $character = $selectors[$i];

            if ($quote !== null) {
                $buffer .= $character;
                if ($character === '\\' && $i + 1 < $length) {
                    $buffer .= $selectors[++$i];
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                $buffer .= $character;

                continue;
            }

            if ($character === '(') {
                $parentheses++;
                $buffer .= $character;

                continue;
            }

            if ($character === ')') {
                $parentheses = max(0, $parentheses - 1);
                $buffer .= $character;

                continue;
            }

            if ($character === '[') {
                $brackets++;
                $buffer .= $character;

                continue;
            }

            if ($character === ']') {
                $brackets = max(0, $brackets - 1);
                $buffer .= $character;

                continue;
            }

            if ($character === ',' && $parentheses === 0 && $brackets === 0) {
                $parts[] = $buffer;
                $buffer = '';

                continue;
            }

            $buffer .= $character;
        }

        if ($buffer !== '') {
            $parts[] = $buffer;
        }

        return $parts;
    }

    private function readUntil(string $css, int &$index, int $length, string $terminators): string
    {
        $start = $index;
        $quote = null;

        while ($index < $length) {
            $character = $css[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index = min($length, $index + 2);

                    continue;
                }
                if ($character === $quote) {
                    $quote = null;
                }
                $index++;

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                $index++;

                continue;
            }

            if ($character === '/' && ($css[$index + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $index + 2);
                $index = $end === false ? $length : $end + 2;

                continue;
            }

            if (str_contains($terminators, $character)) {
                break;
            }

            $index++;
        }

        return substr($css, $start, $index - $start);
    }

    private function readBlock(string $css, int &$index, int $length): string
    {
        $start = $index;
        $depth = 0;
        $quote = null;

        while ($index < $length) {
            $character = $css[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index = min($length, $index + 2);

                    continue;
                }
                if ($character === $quote) {
                    $quote = null;
                }
                $index++;

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                $index++;

                continue;
            }

            if ($character === '/' && ($css[$index + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $index + 2);
                $index = $end === false ? $length : $end + 2;

                continue;
            }

            if ($character === '{') {
                $depth++;
            } elseif ($character === '}') {
                $depth--;
                if ($depth === 0) {
                    $index++;

                    return substr($css, $start, $index - $start);
                }
            }

            $index++;
        }

        return substr($css, $start, $index - $start);
    }

    private function skipIgnorable(string $css, int &$index, int $length): void
    {
        while ($index < $length) {
            if (ctype_space($css[$index])) {
                $index++;

                continue;
            }

            if ($css[$index] === '/' && ($css[$index + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $index + 2);
                $index = $end === false ? $length : $end + 2;

                continue;
            }

            break;
        }
    }

    private function stripComments(string $css): string
    {
        $output = '';
        $length = strlen($css);
        $quote = null;

        for ($i = 0; $i < $length; $i++) {
            $character = $css[$i];

            if ($quote !== null) {
                $output .= $character;
                if ($character === '\\' && $i + 1 < $length) {
                    $output .= $css[++$i];
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                $output .= $character;

                continue;
            }

            if ($character === '/' && ($css[$i + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $i + 2);
                $i = $end === false ? $length : $end + 1;
                $output .= ' ';

                continue;
            }

            $output .= $character;
        }

        return $output;
    }
}
