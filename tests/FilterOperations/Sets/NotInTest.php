<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Sets\NotIn;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

test('binds a relative date as the date it stands for, as the default normalizers run on every value', function () {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $query = DB::table('users');
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('created_at', ['-7 days', 'value'], FilterOperator::NOT_IN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    (new NotIn)->handle($query, $visualizable, $filterData);

    expect($query->getBindings())->toBe(['2026-09-29 00:00:00', 'value']);
});
