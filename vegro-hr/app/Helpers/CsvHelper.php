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

    public static function formatDate($value, string $fallback = ''): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    public static function formatDateTime($value, string $fallback = ''): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y H:i');
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    public static function parseDateForStorage($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d', 'd.m.Y', 'Y.m.d'];
        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $raw);
                if ($date && $date->format($format) === $raw) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $exception) {
                // try next format
            }
        }

        try {
            return \Carbon\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
