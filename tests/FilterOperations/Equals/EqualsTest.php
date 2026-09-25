<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Query\MariaDbFilterOperation;
use Illuminate\Database\Query\Builder;

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

    $filter = new MariaDbFilterOperation;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
