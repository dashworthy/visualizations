<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DateHydrator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

it('applies filters and sorts to query', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $visualizable->shouldReceive('getSelectWith')->andReturn('test_column');
    $visualizable->shouldReceive('getFilterWith')->andReturn('test_column');
    $visualizable->shouldReceive('getField')->andReturn('test_column');
    $visualizable->shouldReceive('getSelectWithBindings')->andReturn([]);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);

    // Through the fluent API: the constructor takes no arguments, so collections passed to it
    // are discarded and this test asserted nothing.
    $gridData = (new VisualizationData)
        ->addAndFilterSet(fn (FilterBuilder $filters) => $filters->equals('test_column', 'test_value'))
        ->addSortAsc('test_column');

    $query->shouldReceive('where')->with(Mockery::on(function ($closure) use ($query): true {
        $closure($query);

        return true;
    }))->andReturnSelf();

    // once(), because an expectation nothing requires cannot fail.
    $query->shouldReceive('whereRaw')->once()->with('test_column = ?', ['test_value'])->andReturnSelf();
    $query->shouldReceive('orderBy')->once()->with('test_column', 'asc')->andReturnSelf();
    $query->shouldReceive('selectRaw')->once()->with('test_column as `test_column`', [])->andReturnSelf();

    $action = GenerateVisualizationQuery::make();
    $result = $action->handle($query, collect([$visualizable]), $gridData);

    expect($result)->toBe($query);
});

it('still sorts and filters an ordinary column', function () {
    // Guards against over-narrowing getMatchingVisualizable(), which sorting and filtering share.
    // Both halves asserted, or a regression in one hides behind the other.
    $query = DB::table('users');

    GenerateVisualizationQuery::make()->handle(
        $query,
        collect([Number::make('users.id', 'ID')]),
        (new VisualizationData)
            ->addAndFilterSet(fn (FilterBuilder $filters) => $filters->equals('column_ID', 7))
            ->addSortAsc('column_ID'),
    );

    expect($query->toRawSql())
        ->toContain('users.id = 7')
        ->toContain('order by');
});

it('selects no expression for a hydrated column', function () {
    // Selecting it anyway emits a broken alias.
    $query = DB::table('users');

    GenerateVisualizationQuery::make()->handle(
        $query,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for(new DateHydrator, 'Notes'),
        ]),
        new VisualizationData,
    );

    expect($query->toRawSql())
        ->toContain('column_ID')
        ->not->toContain('column_Notes');
});

it('ignores a sort on a hydrated column', function () {
    // The schema says not sortable, but a stale client can still ask.
    $query = DB::table('users');

    GenerateVisualizationQuery::make()->handle(
        $query,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for(new DateHydrator, 'Notes'),
        ]),
        (new VisualizationData)->addSortAsc('column_Notes'),
    );

    expect($query->toRawSql())->not->toContain('order by');
});

it('ignores a filter on a hydrated column', function () {
    // Worse than the sort: the filter would splice the column's empty expression into a where.
    $query = DB::table('users');

    GenerateVisualizationQuery::make()->handle(
        $query,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for(new DateHydrator, 'Notes'),
        ]),
        (new VisualizationData)->addAndFilterSet(
            fn (FilterBuilder $filters) => $filters->equals('column_Notes', 'anything'),
        ),
    );

    expect($query->toRawSql())->not->toContain('where');
});
