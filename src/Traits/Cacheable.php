<?php

namespace Dashworthy\Visualizations\Traits;

use Dashworthy\Visualizations\Data\VisualizationData;
use Illuminate\Http\Request;

trait Cacheable
{
    /**
     * The baseline cache-key criteria shared by every visualization type: the
     * active filter sets and sorts. Types that need more (e.g. DataGrids adding
     * pagination) override this method and merge in their own criteria.
     *
     * @return array<string, mixed>
     */
    public function cacheKeyCriteria(Request $request, VisualizationData $data): array
    {
        return [
            'filter_sets' => $data->filterSets->map->toArray()->all(),
            'sorts' => $data->sorts->map->toArray()->all(),
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function cacheDuration(): array
    {
        return [
            (int) config('visualizations.cache.fresh', 300),
            (int) config('visualizations.cache.stale', 600),
        ];
    }
}
