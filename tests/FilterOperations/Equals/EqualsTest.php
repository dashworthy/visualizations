<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Equality\Equals;
use Illuminate\Database\Query\Builder;

test('can handle', function () {
    $filter = new Equals;

    expect($filter->canHandle(FilterOperator::EQUALS))->toBeTrue();
});

test('handles the filter', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('created_at', '2023-01-01 00:00:00', FilterOperator::EQUALS);

    $visualizable->shouldReceive('getFilterWith')->andReturn('created_at');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('created_at = ?', ['2023-01-01 00:00:00'])
        ->andReturnSelf();

    $filter = new Equals;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
