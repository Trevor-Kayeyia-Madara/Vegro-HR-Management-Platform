<?php

namespace App\Helpers;

class CsvHelper
{
    public static function escape($value): string
    {
        if ($value === null) {
            $value = '';
        }

        $value = (string) $value;

        if (str_contains($value, '"')) {
            $value = str_replace('"', '""', $value);
        }

        if (strpbrk($value, ",\n\r\"") !== false) {
            return '"' . $value . '"';
        }

        return $value;
    }

    public static function row(array $values): string
    {
        return implode(',', array_map([self::class, 'escape'], $values)) . "\n";
    }

    public static function normalizeHeader(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = strtolower($value);
        $value = preg_replace('/\s+/', '_', $value);
        return $value;
    }

    public static function splitList(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/[,\|]+/', $value);
        return array_values(array_filter(array_map('trim', $parts), fn ($item) => $item !== ''));
    }
}
