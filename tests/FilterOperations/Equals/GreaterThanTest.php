<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Equality\GreaterThan;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

test('can handle', function () {
    $filter = new GreaterThan;

    expect($filter->canHandle(FilterOperator::GREATER_THAN))->toBeTrue();
});

test('handles the filter', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('quantity', 10, FilterOperator::GREATER_THAN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('quantity');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('quantity > ?', [10])
        ->andReturnSelf();

    $filter = new GreaterThan;
    $result = $filter->handle($query, $visualizable, $filterData);

    expect($result)->toBe($query);
});

test('binds the normalized value', function (mixed $value, mixed $expected) {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $query = DB::table('users');
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('created_at', $value, FilterOperator::GREATER_THAN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('created_at');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    (new GreaterThan)->handle($query, $visualizable, $filterData);

    // A null value still compares with ?, never IS NULL, so it matches no rows.
    expect($query->wheres[0]['sql'])->toBe('created_at > ?')
        ->and($query->getBindings())->toBe([$expected]);
})->with([
    'relative days' => ['-7 days', '2026-09-28'],
    'relative hours' => ['-3 hours', '2026-10-05 12:30:00'],
    'null' => [null, null],
    'null string' => ['null', null],
    'empty string' => ['', null],
    'true string' => ['true', true],
    'false string' => ['false', false],
    'on string' => ['on', true],
    'off string' => ['off', false],
    'integer' => [10, 10],
    'numeric string' => ['10', '10'],
    'float' => [1.5, 1.5],
    'date string' => ['2026-09-29 00:00:00', '2026-09-29 00:00:00'],
]);
