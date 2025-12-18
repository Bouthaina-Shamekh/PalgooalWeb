<?php

namespace App\Support\Sections;

class SectionRegistry
{
    protected static array $sections = [];

    /**
     * Register section type
     */
    public static function register(string $type, array $config): void
    {
        static::$sections[$type] = $config;
    }

    /**
     * Get section config
     */
    public static function get(string $type): ?array
    {
        return static::$sections[$type] ?? null;
    }

    /**
     * Return all registered sections
     */
    public static function all(): array
    {
        return static::$sections;
    }
}
