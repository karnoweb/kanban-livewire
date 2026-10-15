<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Support;

use InvalidArgumentException;

final class ValueGuard
{
    public static function id(string $value, string $field): string
    {
        $value = trim($value);
        $max = (int) config('kanban.limits.id_length', 128);

        if ($value === '' || strlen($value) > $max || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new InvalidArgumentException($field.' must be a non-empty identifier.');
        }

        return $value;
    }

    public static function requiredText(string $value, string $field, int $max = 300): string
    {
        $value = trim($value);

        if ($value === '' || strlen($value) > $max) {
            throw new InvalidArgumentException($field.' is required and must respect the length limit.');
        }

        return $value;
    }

    public static function optionalText(?string $value, int $max = 5000): ?string
    {
        if ($value === null) {
            return null;
        }

        if (strlen($value) > $max) {
            throw new InvalidArgumentException('Text exceeds the length limit.');
        }

        return $value;
    }

    /** @param array<string, mixed> $metadata */
    public static function metadata(array $metadata): array
    {
        $max = (int) config('kanban.limits.metadata_bytes', 8192);

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || $key === '' || is_array($value) || is_object($value)) {
                throw new InvalidArgumentException('Metadata must be a map of scalar values.');
            }
        }

        $encoded = json_encode($metadata, JSON_THROW_ON_ERROR);

        if (strlen($encoded) > $max) {
            throw new InvalidArgumentException('Metadata exceeds the size cap.');
        }

        return $metadata;
    }

    /** @param array<string, mixed> $formData */
    public static function formData(array $formData): array
    {
        foreach ($formData as $key => $value) {
            if (! is_string($key) || is_object($value)) {
                throw new InvalidArgumentException('Form data must be a scalar map.');
            }

            if (is_array($value)) {
                foreach ($value as $nested) {
                    if (is_array($nested) || is_object($nested)) {
                        throw new InvalidArgumentException('Form data nesting is not allowed.');
                    }
                }
            }
        }

        return self::metadata(self::flattenForSize($formData));
    }

    /** @param array<string, mixed> $formData
     * @return array<string, scalar|null>
     */
    private static function flattenForSize(array $formData): array
    {
        $flat = [];

        foreach ($formData as $key => $value) {
            $flat[$key] = is_array($value) ? implode(',', array_map('strval', $value)) : $value;
        }

        return $flat;
    }
}
