<?php

namespace App\Support;

class Env
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower((string) $value);
        return in_array($normalized, ['1', 'true', 'on', 'yes'], true) ? true : ($normalized === '0' || $normalized === 'false' ? false : $default);
    }

    /**
     * @return array<int, string>
     */
    public static function array(string $key, array $default = []): array
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        $items = array_map('trim', explode(',', (string) $value));
        $items = array_filter($items, static fn ($item) => $item !== '');
        return array_values($items);
    }
}
