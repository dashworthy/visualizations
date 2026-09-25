<?php

use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DeclaredColumnsDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;
use Illuminate\Support\Collection;

/** Rows as they arrive from the query builder: plain objects keyed by prefixed field. */
function gridHydrationRows(array $ids): Collection
{
    return collect($ids)->map(fn ($id) => (object) ['column_ID' => $id, 'column_Name' => 'n']);
}

test('ignores columns that are not hydrated', function () {
    $rows = gridHydrationRows([1]);

    DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        Text::make('users.name', 'Name'),
    ]))->hydrate($rows);

    expect((array) $rows->first())->toBe(['column_ID' => 1, 'column_Name' => 'n']);
});

test('resolves the declared field to the prefixed one the row carries', function () {
    // keyedBy() returns 'ID'; the row carries 'column_ID'. The author never writes the prefix.
    $hydrator = new StaticHydrator([7 => 'seven'], 'ID');
    $rows = gridHydrationRows([7]);

    DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for($hydrator, 'Notes'),
    ]))->hydrate($rows);

    expect($hydrator->keysSeen[0]->all())->toBe([7])
        ->and($rows->first()->column_Notes)->toBe('seven');
});

test('returns the very collection it was handed', function () {
    $rows = gridHydrationRows([1]);

    $returned = DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for(new StaticHydrator([1 => 'first']), 'Notes'),
    ]))->hydrate($rows);

    expect($returned)->toBe($rows);
});

test('throws when the keyed field is not a column on the grid', function () {
    $grid = DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for(new StaticHydrator([], 'Nonexistent'), 'Notes'),
    ]));

    expect(fn () => $grid->hydrate(gridHydrationRows([1])))
        ->toThrow(Exception::class, 'Nonexistent');
});

test('throws rather than key off a floating filter of the same name', function () {
    // A floating filter is never selected, so keying off one would read a property no row has and
    // null the whole column silently. It has to be the loud failure, not the silent one.
    $hydrator = new StaticHydrator([], 'Joined');

    $grid = DeclaredColumnsDataGrid::with(
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for($hydrator, 'Notes'),
        ]),
        collect([DateRange::make('DATE(users.created_at)', 'Joined')]),
    );

    expect(fn () => $grid->hydrate(gridHydrationRows([1])))
        ->toThrow(Exception::class, 'Joined')
        ->and($hydrator->resolveCallCount)->toBe(0);
});

test('throws when a hydrated column collides with the field it keys on', function () {
    $grid = DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for(new StaticHydrator([], 'ID'), 'ID'),
    ]));

    expect(fn () => $grid->hydrate(gridHydrationRows([1])))
        ->toThrow(Exception::class, 'collides');
});

test('will not key one hydrated column off another', function () {
    $grid = DeclaredColumnsDataGrid::with(collect([
        Text::make('users.name', 'Name'),
        HydratedColumn::for(new StaticHydrator([], 'Name'), 'ID'),
        HydratedColumn::for(new StaticHydrator([], 'ID'), 'Notes'),
    ]));

    expect(fn () => $grid->hydrate(gridHydrationRows([1])))
        ->toThrow(Exception::class, "keys on 'ID'");
});
