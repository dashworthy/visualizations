<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Sets\NotIn;
use Illuminate\Database\Query\Builder;

test('can handle', function () {
    $filter = new NotIn;

    expect($filter->canHandle(FilterOperator::NOT_IN))->toBeTrue();
});

test('handles without nulls', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('key', ['value1', 'value2'], FilterOperator::NOT_IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldNotReceive('orWhereNotNull')
        ->with('key')
        ->andReturnSelf();

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('key NOT IN (?,?)', ['value1', 'value2'])
        ->andReturnSelf();

    $filter = new NotIn;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});

test('handles with nulls', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('key', ['value1', 'value2'], FilterOperator::NOT_IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('key NOT IN (?,?)', ['value1', 'value2'])
        ->andReturnSelf();

    $query->shouldReceive('orWhereNotNull')
        ->with('key')
        ->andReturnSelf();

    $filter = new NotIn;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
