<?php

use Illuminate\Database\Query\Builder;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Equality\LessThanOrEqualTo;

test('can handle', function () {
    $filter = new LessThanOrEqualTo;

    expect($filter->canHandle(FilterOperator::LESS_THAN_OR_EQUAL_TO))->toBeTrue();
});

test('handles the filter', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('quantity', 10, FilterOperator::LESS_THAN_OR_EQUAL_TO);

    $visualizable->shouldReceive('getFilterWith')->andReturn('quantity');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('quantity <= ?', [10])
        ->andReturnSelf();

    $filter = new LessThanOrEqualTo;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
