<?php

namespace Dashworthy\Visualizations\Contracts;

use Dashworthy\Visualizations\Data\VisualizationData;
use Illuminate\Http\Request;

interface ShouldCache
{
    /**
     * The criteria that uniquely identify this request's result set.
     * Merged with auto-identity (visualization key + auth id) and hashed into the cache key.
     *
     * @return array<string, mixed>
     */
    public function cacheKeyCriteria(Request $request, VisualizationData $data): array;

    /**
     * The [fresh, stale] window pair (in seconds) passed to Cache::flexible().
     *
     * @return array{0: int, 1: int}
     */
    public function cacheDuration(): array;
}
