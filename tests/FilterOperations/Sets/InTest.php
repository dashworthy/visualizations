<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Query\MariaDbFilterOperation;
use Illuminate\Database\Query\Builder;

test('handles not null values', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('created_at', ['value1', 'value2'], FilterOperator::IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('key IN (?,?)', ['value1', 'value2'])
        ->andReturnSelf();

    $query->shouldNotReceive('orWhereNull')
        ->with('key')
        ->andReturnSelf();

    $filter = new MariaDbFilterOperation;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});

test('handles with null values', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('created_at', ['value1', null], FilterOperator::IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('(key IN (?) OR key IS NULL)', ['value1'])
        ->andReturnSelf();

    $query->shouldNotReceive('orWhereNull');

    $filter = new MariaDbFilterOperation;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});

test('handles only a null', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('created_at', [null], FilterOperator::IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('key IS NULL', [])
        ->andReturnSelf();

    expect((new MariaDbFilterOperation)->handle($query, $visualizable, $filterData))->toBe($query);
});

test('a null clause follows the having method', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('total', [1, null], FilterOperator::IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('SUM(amount)');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(true);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('havingRaw')
        ->once()
        ->with('(SUM(amount) IN (?) OR SUM(amount) IS NULL)', [1])
        ->andReturnSelf();

    $query->shouldNotReceive('orWhereNull');

    expect((new MariaDbFilterOperation)->handle($query, $visualizable, $filterData))->toBe($query);
});
