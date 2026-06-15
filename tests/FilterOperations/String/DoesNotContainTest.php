<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Text\DoesNotContain;
use Illuminate\Database\Query\Builder;

test('can handle', function () {
    $filter = new DoesNotContain;

    expect($filter->canHandle(FilterOperator::STRING_DOES_NOT_CONTAIN))->toBeTrue();
});

test('handles the filter', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('name', 'value', FilterOperator::STRING_DOES_NOT_CONTAIN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('name');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('(name NOT LIKE ? OR name IS NULL)', ['%value%'])
        ->andReturnSelf();

    $filter = new DoesNotContain;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
