<?php

use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Query\HydrateVisualizationRows;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;
use Illuminate\Support\Collection;

/** Rows as they arrive from the query builder: plain objects keyed by prefixed field. */
function rowsKeyedById(array $ids): Collection
{
    return collect($ids)->map(fn ($id) => (object) ['column_ID' => $id, 'column_Name' => 'n']);
}

it('resolves once for a whole page', function () {
    $hydrator = new StaticHydrator;

    HydrateVisualizationRows::make()->handle(
        rowsKeyedById(range(1, 250)),
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for($hydrator, 'Notes'),
        ]),
    );

    expect($hydrator->resolveCallCount)->toBe(1);
});

it('does not call the hydrator when there are no keys to look up', function () {
    $empty = new StaticHydrator;
    $allNull = new StaticHydrator;

    HydrateVisualizationRows::make()->handle(
        rowsKeyedById([]),
        collect([Number::make('users.id', 'ID'), HydratedColumn::for($empty, 'Notes')]),
    );

    HydrateVisualizationRows::make()->handle(
        rowsKeyedById([null, null]),
        collect([Number::make('users.id', 'ID'), HydratedColumn::for($allNull, 'Notes')]),
    );

    expect($empty->resolveCallCount)->toBe(0)
        ->and($allNull->resolveCallCount)->toBe(0);
});

it('hands over distinct keys with nulls dropped', function () {
    $hydrator = new StaticHydrator;

    HydrateVisualizationRows::make()->handle(
        rowsKeyedById([3, 1, 3, null, 1, null]),
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for($hydrator, 'Notes'),
        ]),
    );

    expect($hydrator->keysSeen[0]->all())->toBe([3, 1]);
});

it('writes the resolved value onto every row', function () {
    $rows = rowsKeyedById([1, 2]);

    HydrateVisualizationRows::make()->handle(
        $rows,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for(new StaticHydrator([1 => 'first', 2 => 'second']), 'Notes'),
        ]),
    );

    expect($rows->pluck('column_Notes')->all())->toBe(['first', 'second']);
});

it('leaves a row null when its key is missing from the map', function () {
    $rows = rowsKeyedById([1, 99]);

    HydrateVisualizationRows::make()->handle(
        $rows,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for(new StaticHydrator([1 => 'first']), 'Notes'),
        ]),
    );

    expect($rows->pluck('column_Notes')->all())->toBe(['first', null]);
});

it('leaves a row null when its own key is null', function () {
    $rows = rowsKeyedById([null]);

    HydrateVisualizationRows::make()->handle(
        $rows,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for(new StaticHydrator([1 => 'first']), 'Notes'),
        ]),
    );

    expect($rows->first()->column_Notes)->toBeNull();
});

it('ignores columns that are not hydrated', function () {
    $rows = rowsKeyedById([1]);

    HydrateVisualizationRows::make()->handle(
        $rows,
        collect([
            Number::make('users.id', 'ID'),
            Text::make('users.name', 'Name'),
        ]),
    );

    expect((array) $rows->first())->toBe(['column_ID' => 1, 'column_Name' => 'n']);
});

it('resolves the declared field to the prefixed one the row carries', function () {
    // keyedBy() returns 'ID'; the row carries 'column_ID'. The author never writes the prefix.
    $hydrator = new StaticHydrator([7 => 'seven'], 'ID');
    $rows = rowsKeyedById([7]);

    HydrateVisualizationRows::make()->handle(
        $rows,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for($hydrator, 'Notes'),
        ]),
    );

    expect($hydrator->keysSeen[0]->all())->toBe([7])
        ->and($rows->first()->column_Notes)->toBe('seven');
});

it('throws when the keyed field is not a column on the grid', function () {
    $columns = collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for(new StaticHydrator([], 'Nonexistent'), 'Notes'),
    ]);

    expect(fn () => HydrateVisualizationRows::make()->handle(rowsKeyedById([1]), $columns))
        ->toThrow(Exception::class, 'Nonexistent');
});

it('throws rather than key off a floating filter of the same name', function () {
    // getVisualizables() is columns concat floating filters, and a floating filter is never
    // selected — so keying off one yields a missing property on every row, an all-null column,
    // and no query at all. It has to be the loud failure, not the silent one.
    $hydrator = new StaticHydrator([], 'Joined');

    $columns = collect([
        Number::make('users.id', 'ID'),
        DateRange::make('DATE(users.created_at)', 'Joined'),
        HydratedColumn::for($hydrator, 'Notes'),
    ]);

    expect(fn () => HydrateVisualizationRows::make()->handle(rowsKeyedById([1]), $columns))
        ->toThrow(Exception::class, 'Joined')
        ->and($hydrator->resolveCallCount)->toBe(0);
});

it('lets an exception from the hydration source through', function () {
    $columns = collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for(new StaticHydrator([], 'ID', throws: true), 'Notes'),
    ]);

    expect(fn () => HydrateVisualizationRows::make()->handle(rowsKeyedById([1]), $columns))
        ->toThrow(RuntimeException::class, 'the hydration source is down');
});

it('returns the very collection it was handed', function () {
    $rows = rowsKeyedById([1]);

    $returned = HydrateVisualizationRows::make()->handle(
        $rows,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for(new StaticHydrator([1 => 'first']), 'Notes'),
        ]),
    );

    expect($returned)->toBe($rows);
});

it('keeps keys apart that only compare loosely equal', function () {
    // '01' == 1 in PHP but indexes a different array bucket, so folding them loses a row's value.
    $hydrator = new StaticHydrator(['01' => 'padded', 1 => 'one']);
    $rows = rowsKeyedById(['01', 1]);

    HydrateVisualizationRows::make()->handle(
        $rows,
        collect([
            Number::make('users.id', 'ID'),
            HydratedColumn::for($hydrator, 'Notes'),
        ]),
    );

    expect($hydrator->keysSeen[0]->all())->toBe(['01', 1])
        ->and($rows->pluck('column_Notes')->all())->toBe(['padded', 'one']);
});

it('throws when the keyed field holds something no array can be indexed by', function () {
    $columns = collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for(new StaticHydrator, 'Notes'),
    ]);

    expect(fn () => HydrateVisualizationRows::make()->handle(rowsKeyedById([1.5]), $columns))
        ->toThrow(Exception::class, 'float');
});

it('throws when a hydrated column collides with the field it keys on', function () {
    $columns = collect([
        Number::make('users.id', 'ID'),
        HydratedColumn::for(new StaticHydrator([], 'ID'), 'ID'),
    ]);

    expect(fn () => HydrateVisualizationRows::make()->handle(rowsKeyedById([1]), $columns))
        ->toThrow(Exception::class, 'collides');
});

it('will not key one hydrated column off another', function () {
    $columns = collect([
        Text::make('users.name', 'Name'),
        HydratedColumn::for(new StaticHydrator([], 'Name'), 'ID'),
        HydratedColumn::for(new StaticHydrator([], 'ID'), 'Notes'),
    ]);

    expect(fn () => HydrateVisualizationRows::make()->handle(rowsKeyedById([1]), $columns))
        ->toThrow(Exception::class, "keys on 'ID'");
});
