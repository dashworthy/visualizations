<?php

use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CachedUserDataGrid;

it('builds baseline criteria with filter sets and sorts', function () {
    $grid = new CachedUserDataGrid;

    $request = DataGridDataRequest::create('/grid-data', 'POST', [
        'per_page' => 250,
        'filter_sets' => [],
        'sorts' => [],
    ]);

    $data = VisualizationData::fromDataGridRequest($request);

    $criteria = $grid->cacheKeyCriteria($request, $data);

    expect($criteria)->toHaveKeys(['filter_sets', 'sorts', 'pagination']);
    expect($criteria['filter_sets'])->toBe([]);
    expect($criteria['sorts'])->toBe([]);
});

it('includes pagination criteria for data grids', function () {
    $grid = new CachedUserDataGrid;

    $request = DataGridDataRequest::create('/grid-data', 'POST', [
        'per_page' => 100,
        'page' => 2,
        'filter_sets' => [],
        'sorts' => [],
    ]);

    $data = VisualizationData::fromDataGridRequest($request);

    $criteria = $grid->cacheKeyCriteria($request, $data);

    expect($criteria['pagination']['per_page'])->toBe(100);
    expect($criteria['pagination']['page'])->toBe(2);
    expect($criteria['pagination']['first'])->toBeNull();
    expect($criteria['pagination']['last'])->toBeNull();
});

it('returns a fresh and stale duration pair in seconds', function () {
    config()->set('visualizations.cache.fresh', 300);
    config()->set('visualizations.cache.stale', 600);

    $grid = new CachedUserDataGrid;

    expect($grid->cacheDuration())->toBe([300, 600]);
});
