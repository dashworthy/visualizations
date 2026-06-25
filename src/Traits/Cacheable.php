<?php

namespace Dashworthy\Visualizations\Traits;

use Dashworthy\Visualizations\Data\VisualizationData;
use Illuminate\Http\Request;

trait Cacheable
{
    /**
     * @return array<string, mixed>
     */
    public function cacheKeyCriteria(Request $request, VisualizationData $data): array
    {
        $criteria = [
            'filter_sets' => $data->filterSets->map->toArray()->all(),
            'sorts' => $data->sorts->map->toArray()->all(),
        ];

        if (method_exists($this, 'cachePaginationCriteria')) {
            $criteria['pagination'] = $this->cachePaginationCriteria($request);
        }

        return $criteria;
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
