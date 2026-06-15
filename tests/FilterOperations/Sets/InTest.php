<?php

use Illuminate\Database\Query\Builder;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Sets\In;

test('can handle', function () {
    $filter = new In;

    expect($filter->canHandle(FilterOperator::IN))->toBeTrue();
});

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

    $filter = new In;
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
        ->with('key IN (?,?)', ['value1', null])
        ->andReturnSelf();

    $query->shouldReceive('orWhereNull')
        ->once()
        ->with('key')
        ->andReturnSelf();

    $filter = new In;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});
