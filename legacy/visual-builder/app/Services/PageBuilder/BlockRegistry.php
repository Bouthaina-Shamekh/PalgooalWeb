<?php

namespace App\Services\PageBuilder;

/**
 * BlockRegistry
 *
 * This service is responsible for:
 * - Reading block definitions from the configuration (config/page_builder.php).
 * - Providing helper methods to:
 *   - List all blocks
 *   - List blocks for a specific context (marketing, tenant, ...)
 *   - Find a specific block by its key
 *
 * In the future, this can be extended to merge:
 * - Core blocks (from config)
 * - Custom developer blocks (from database)
 */
class BlockRegistry
{
    /**
     * All registered blocks loaded from configuration.
     *
     * @var array<string, array>
     */
    protected array $blocks;

    /**
     * Load all blocks from config when the service is constructed.
     */
    public function __construct()
    {
        // Load blocks from the page_builder config file
        $this->blocks = config('page_builder.blocks', []);
    }

    /**
     * Get all registered blocks (no context filtering).
     *
     * @return array<string, array>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * Get all blocks that are available for a specific context.
     *
     * Example:
     *  - context: "marketing"
     *  - context: "tenant"
     *
     * @param  string  $context
     * @return array<string, array>
     */
    public function allForContext(string $context): array
    {
        return collect($this->blocks)
            ->filter(function (array $block) use ($context) {
                $contexts = $block['contexts'] ?? [];

                // If no contexts are defined, assume the block is global.
                if (empty($contexts)) {
                    return true;
                }

                return in_array($context, $contexts, true);
            })
            ->all();
    }

    /**
     * Find a single block definition by its key.
     *
     * Returns null if the block key does not exist.
     *
     * @param  string  $key
     * @return array|null
     */
    public function find(string $key): ?array
    {
        return $this->blocks[$key] ?? null;
    }

    /**
     * Get the default content structure for a given block key.
     *
     * This is used when creating a new section to seed its JSON content,
     * so the admin starts with pre-filled values instead of an empty form.
     *
     * @param  string  $key
     * @return array
     */
    public function defaultContent(string $key): array
    {
        $block = $this->find($key);

        if (! $block) {
            return [];
        }

        return $block['default_content'] ?? [];
    }
}
