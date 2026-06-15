<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Equality\GreaterThanOrEqualTo;
use Illuminate\Database\Query\Builder;

test('can handle', function () {
    $filter = new GreaterThanOrEqualTo;

    expect($filter->canHandle(FilterOperator::GREATER_THAN_OR_EQUAL_TO))->toBeTrue();
});

test('handles the filter', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('quantity', 10, FilterOperator::GREATER_THAN_OR_EQUAL_TO);

    $visualizable->shouldReceive('getFilterWith')->andReturn('quantity');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('quantity >= ?', [10])
        ->andReturnSelf();

    $filter = new GreaterThanOrEqualTo;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
