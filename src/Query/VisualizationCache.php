<?php

namespace Dashworthy\Visualizations\Query;

use Closure;
use Dashworthy\Visualizations\Contracts\ShouldCache;
use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Data\VisualizationData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Caches the results of a visualization's data query to avoid redundant database hits.
 *
 * Caching is opt-in: results are only cached when the visualization implements
 * {@see ShouldCache} and caching is enabled in config. Every other visualization is
 * run straight through with no caching, preserving its existing behavior exactly.
 *
 * When caching applies, results are stored via Laravel's Cache::flexible() so a stale
 * value can be served immediately while a fresh value regenerates in the background —
 * smoothing over expirations rather than stampeding the database on every miss.
 *
 * The cache is read from / written to the application's default cache store. There is
 * no manual invalidation: entries expire purely via their fresh/stale windows.
 */
class VisualizationCache
{
    /**
     * Create a new action instance via the container.
     *
     * Resolving through app() lets consumers bind their own implementation
     * (e.g. a subclass with custom keying or store logic) to this class and
     * have it used everywhere the action is invoked.
     */
    public static function make(): self
    {
        return app(self::class);
    }

    /**
     * Run the data query through the cache, returning the results alongside whether
     * they were served from cache.
     *
     * When caching is disabled globally or the visualization does not implement
     * {@see ShouldCache}, the callback is invoked directly and reported as a cache
     * miss (fromCache: false) — no key is built and the cache is never touched.
     *
     * Otherwise the callback is wrapped in Cache::flexible() using the visualization's
     * [fresh, stale] window. A `$ran` flag records whether the callback actually
     * executed: it only runs on a hard miss (or a synchronous refresh past the stale
     * window), so a value served from cache — including a stale value whose refresh is
     * deferred to after the response — is correctly reported as a hit.
     *
     * @param  VisualizationContract  $visualization  The visualization whose data is being produced.
     * @param  Request  $request  The incoming data request, forwarded to cacheKeyCriteria().
     * @param  VisualizationData  $data  The parsed filter sets and sorts for this request.
     * @param  Closure  $callback  Produces the results to cache (e.g. rows, a paginator, or a scalar row).
     */
    public function handle(
        VisualizationContract $visualization,
        Request $request,
        VisualizationData $data,
        Closure $callback,
    ): VisualizationCacheResult {
        // Opt-out fast path: run the query directly when caching is off or not requested.
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

        // The callback only fires on a miss; if it never ran, the value came from cache.
        return new VisualizationCacheResult($results, ! $ran);
    }

    /**
     * Build the deterministic cache key for this request.
     *
     * The visualization key and authenticated user id are always folded in here, by the
     * action itself, rather than relying on cacheKeyCriteria(). The consumer's criteria
     * are nested under a separate 'criteria' entry so they can never overwrite this
     * identity — cached data therefore stays isolated per user even when a visualization
     * overrides cacheKeyCriteria(). The payload is ksort()ed before hashing so its
     * top-level ordering is stable regardless of insertion order.
     *
     * Format: "{prefix}:{visualization_key}:{sha1 of the json-encoded payload}".
     */
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
