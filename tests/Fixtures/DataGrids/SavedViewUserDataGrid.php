<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

/**
 * A user grid that defines every optional saved-view hook, so the dataGrid route macro registers each views route.
 */
class SavedViewUserDataGrid extends UserDataGrid
{
    /**
     * @return array<int, mixed>
     */
    public function handleViews(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function handleViewStore(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function handleViewUpdate(string $view): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function handleViewDefault(string $view): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function handleViewClearDefault(string $view): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function handleViewDestroy(string $view): array
    {
        return [];
    }
}
