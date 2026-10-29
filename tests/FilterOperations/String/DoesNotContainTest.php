<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Text\DoesNotContain;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

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

test('binds the visualizable bindings for each reference to it', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('name', 'value', FilterOperator::STRING_DOES_NOT_CONTAIN);

    $visualizable->shouldReceive('getFilterWith')->andReturn('coalesce(name, ?)');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn(['none']);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with('(coalesce(name, ?) NOT LIKE ? OR coalesce(name, ?) IS NULL)', ['none', '%value%', 'none'])
        ->andReturnSelf();

    (new DoesNotContain)->handle($query, $visualizable, $filterData);
});

test('excludes matching rows from a column whose expression binds a value', function () {
    // Bound once, the second reference took no value; SQLite reads a missing value as null, so every row matched.
    DB::table('users')->insert([
        ['name' => 'Ada', 'email' => 'ada@example.com'],
        ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]);
    $query = DB::table('users');

    GenerateVisualizationQuery::make()->handle(
        $query,
        collect([Text::make('(users.name || ?)', 'Name', ['!'])]),
        (new VisualizationData)->addAndFilterSet(fn (FilterBuilder $filters) => $filters->doesNotContain('column_Name', 'Ada')),
    );

    expect($query->pluck('column_Name')->all())->toBe(['Bob!']);
});
