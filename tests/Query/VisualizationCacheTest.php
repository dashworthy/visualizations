<?php

use Dashworthy\Visualizations\Contracts\ShouldCache;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Query\VisualizationCache;
use Dashworthy\Visualizations\Query\VisualizationCacheResult;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CachedUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('cache.default', 'array');
    config()->set('visualizations.cache.enabled', true);
    config()->set('visualizations.cache.fresh', 300);
    config()->set('visualizations.cache.stale', 600);
});

function cacheRequest(array $payload = ['filter_sets' => [], 'sorts' => []]): DataGridDataRequest
{
    return DataGridDataRequest::create('/grid-data', 'POST', $payload);
}

it('runs the callback once and serves subsequent calls from cache', function () {
    $grid = new CachedUserDataGrid;
    $request = cacheRequest();
    $data = VisualizationData::fromDataGridRequest($request);

    $calls = 0;
    $callback = function () use (&$calls) {
        $calls++;

        return collect(['value']);
    };

    $first = VisualizationCache::make()->handle($grid, $request, $data, $callback);
    $second = VisualizationCache::make()->handle($grid, $request, $data, $callback);

    expect($first)->toBeInstanceOf(VisualizationCacheResult::class);
    expect($calls)->toBe(1);
    expect($first->fromCache)->toBeFalse();
    expect($second->fromCache)->toBeTrue();
    expect($second->results->all())->toBe(['value']);
});

it('bypasses caching for visualizations that do not implement ShouldCache', function () {
    $grid = new UserDataGrid;
    $request = cacheRequest();
    $data = VisualizationData::fromDataGridRequest($request);

    $calls = 0;
    $callback = function () use (&$calls) {
        $calls++;

        return collect(['value']);
    };

    $first = VisualizationCache::make()->handle($grid, $request, $data, $callback);
    $second = VisualizationCache::make()->handle($grid, $request, $data, $callback);

    expect($calls)->toBe(2);
    expect($first->fromCache)->toBeFalse();
    expect($second->fromCache)->toBeFalse();
});

it('bypasses caching when globally disabled', function () {
    config()->set('visualizations.cache.enabled', false);

    $grid = new CachedUserDataGrid;
    $request = cacheRequest();
    $data = VisualizationData::fromDataGridRequest($request);

    $calls = 0;
    $callback = function () use (&$calls) {
        $calls++;

        return collect(['value']);
    };

    VisualizationCache::make()->handle($grid, $request, $data, $callback);
    $second = VisualizationCache::make()->handle($grid, $request, $data, $callback);

    expect($calls)->toBe(2);
    expect($second->fromCache)->toBeFalse();
});

it('keys cache entries by authenticated user', function () {
    $grid = new CachedUserDataGrid;
    $request = cacheRequest();
    $data = VisualizationData::fromDataGridRequest($request);

    $calls = 0;
    $callback = function () use (&$calls) {
        $calls++;

        return collect(['value']);
    };

    $this->be(new GenericUser(['id' => 1]));
    VisualizationCache::make()->handle($grid, $request, $data, $callback);

    $this->be(new GenericUser(['id' => 2]));
    $other = VisualizationCache::make()->handle($grid, $request, $data, $callback);

    expect($calls)->toBe(2);
    expect($other->fromCache)->toBeFalse();
});

it('consumer criteria cannot displace cache key identity', function () {
    $grid = new class extends DataGrid implements ShouldCache
    {
        public function getColumns(): Collection
        {
            return collect([
                Number::make('users.id', 'ID')->asRowKey(),
            ]);
        }

        public function getQuery(): Builder
        {
            return DB::table('users');
        }

        public function cacheKeyCriteria(Request $request, VisualizationData $data): array
        {
            return ['auth_id' => 'attacker'];
        }
    };

    $request = cacheRequest();
    $data = VisualizationData::fromDataGridRequest($request);

    $calls = 0;
    $callback = function () use (&$calls) {
        $calls++;

        return collect(['value']);
    };

    $this->be(new GenericUser(['id' => 1]));
    VisualizationCache::make()->handle($grid, $request, $data, $callback);

    $this->be(new GenericUser(['id' => 2]));
    $second = VisualizationCache::make()->handle($grid, $request, $data, $callback);

    expect($calls)->toBe(2);
    expect($second->fromCache)->toBeFalse();
});

it('keys cache entries by differing criteria', function () {
    $grid = new CachedUserDataGrid;

    $requestA = cacheRequest(['per_page' => 100, 'filter_sets' => [], 'sorts' => []]);
    $requestB = cacheRequest(['per_page' => 250, 'filter_sets' => [], 'sorts' => []]);

    $dataA = VisualizationData::fromDataGridRequest($requestA);
    $dataB = VisualizationData::fromDataGridRequest($requestB);

    $calls = 0;
    $callback = function () use (&$calls) {
        $calls++;

        return collect(['value']);
    };

    VisualizationCache::make()->handle($grid, $requestA, $dataA, $callback);
    $second = VisualizationCache::make()->handle($grid, $requestB, $dataB, $callback);

    expect($calls)->toBe(2);
    expect($second->fromCache)->toBeFalse();
});
