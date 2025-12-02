<?php

namespace App\Support\Blocks;

/**
 * BlockRegistry:
 * A simple registry that maps section "type" → render callback.
 *
 * Later it will support:
 * - variants
 * - marketing vs tenant contexts
 * - developer custom blocks
 */
class BlockRegistry
{
    /**
     * List of registered blocks.
     * Example:
     *  'hero' => callable
     */
    protected static array $blocks = [];

    /**
     * Register a block with a unique type.
     */
    public static function register(string $type, callable $callback): void
    {
        static::$blocks[$type] = $callback;
    }

    /**
     * Get block callback for a section.
     */
    public static function resolve(string $type): ?callable
    {
        return static::$blocks[$type] ?? null;
    }
}
