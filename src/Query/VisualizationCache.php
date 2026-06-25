<?php

namespace Dashworthy\Visualizations\Query;

use Closure;
use Dashworthy\Visualizations\Contracts\ShouldCache;
use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Data\VisualizationData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VisualizationCache
{
    public static function make(): self
    {
        return new self;
    }

    public function handle(
        VisualizationContract $visualization,
        Request $request,
        VisualizationData $data,
        Closure $callback,
    ): VisualizationCacheResult {
        if (! config('visualizations.cache.enabled', true) || ! $visualization instanceof ShouldCache) {
            return new VisualizationCacheResult($callback(), false);
        }

        $key = $this->buildKey($visualization, $request, $data);
        [$fresh, $stale] = $visualization->cacheDuration();

        $ran = false;
        $results = Cache::flexible($key, [$fresh, $stale], function () use ($callback, &$ran) {
            $ran = true;

            return $callback();
        });

        return new VisualizationCacheResult($results, ! $ran);
    }

    private function buildKey(
        VisualizationContract&ShouldCache $visualization,
        Request $request,
        VisualizationData $data,
    ): string {
        $payload = [
            'visualization_key' => $visualization->getVisualizationKey(),
            'auth_id' => auth()->id(),
            'criteria' => $visualization->cacheKeyCriteria($request, $data),
        ];

        ksort($payload);

        $prefix = config('visualizations.cache.prefix', 'visualizations');

        return $prefix.':'.$visualization->getVisualizationKey().':'.sha1((string) json_encode($payload));
    }
}
