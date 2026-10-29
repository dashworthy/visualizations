<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Text\EndsWith;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

test('binds a relative date as typed, since the default normalizers do not run here', function () {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $query = DB::table('users');
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('name', '-7 days', FilterOperator::STRING_ENDS_WITH);

    $visualizable->shouldReceive('getFilterWith')->andReturn('name');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    (new EndsWith)->handle($query, $visualizable, $filterData);

    expect($query->getBindings())->toBe(['%-7 days']);
});
