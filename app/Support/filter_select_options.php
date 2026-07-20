<?php

if (! function_exists('filter_select_options')) {
    /**
     * Preserve integer option keys (array_merge reindexes them).
     *
     * @param  array<int|string, string>  $options
     * @return array<int|string, string>
     */
    function filter_select_options(array $options, string $allLabel = 'Tümü'): array
    {
        return ['all' => $allLabel] + $options;
    }
}
