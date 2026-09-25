<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Query\MariaDbFilterOperation;
use Illuminate\Database\Query\Builder;

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

    $filter = new MariaDbFilterOperation;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});

test('handles with nulls', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('key', ['value1', null], FilterOperator::NOT_IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('(key NOT IN (?) AND key IS NOT NULL)', ['value1'])
        ->andReturnSelf();

    $query->shouldNotReceive('orWhereNotNull');

    $filter = new MariaDbFilterOperation;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});

test('handles only a null', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('key', [null], FilterOperator::NOT_IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('key IS NOT NULL', [])
        ->andReturnSelf();

    expect((new MariaDbFilterOperation)->handle($query, $visualizable, $filterData))->toBe($query);
});
