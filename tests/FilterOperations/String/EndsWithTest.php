<?php

use Illuminate\Database\Query\Builder;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Text\EndsWith;

test('can handle', function () {
    $filter = new EndsWith;

    expect($filter->canHandle(FilterOperator::STRING_ENDS_WITH))->toBeTrue();
});

test('handles the filter', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('name', 'value', FilterOperator::STRING_ENDS_WITH);

    $visualizable->shouldReceive('getFilterWith')->andReturn('name');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('name LIKE ?', ['%value'])
        ->andReturnSelf();

    $filter = new EndsWith;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
