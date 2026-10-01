<?php

use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DateHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\HydratorProbe;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;
use Illuminate\Support\Collection;

/** Rows as they arrive from the query builder: plain objects keyed by prefixed field. */
function columnHydrationRows(array $ids): Collection
{
    return collect($ids)->map(fn ($id) => (object) ['column_ID' => $id, 'column_Name' => 'n']);
}

test('field is prefixed like any other column', function () {
    $column = HydratedColumn::for(new DateHydrator, 'Notes');

    expect($column->getField())->toBe('column_Notes');
});

test('is never sortable or filterable', function () {
    // Column defaults both to true, so this fails if the overrides go.
    $column = HydratedColumn::for(new DateHydrator, 'Notes');

    expect($column->toArray())
        ->toMatchArray(['is_sortable' => false, 'is_filterable' => false]);
});

test('contributes no expression for the query generator to select', function () {
    // The generator's exclusion is only safe because there is nothing here to select.
    $column = HydratedColumn::for(new DateHydrator, 'Notes');

    expect($column->getSelectWith())->toBe('')
        ->and($column->getSelectWithBindings())->toBe([]);
});

test('reports that it carries no expression', function () {
    // What the query generator branches on: it must not reach for a concrete column class to
    // learn that a hydrated column has no SQL to select, sort, or filter by.
    $column = HydratedColumn::for(new DateHydrator, 'Notes');

    expect($column->hasExpression())->toBeFalse()
        ->and(Text::make('users.name', 'Name')->hasExpression())->toBeTrue();
});

test('says so plainly when built without a hydrator', function () {
    // make() is final upstream, so it stays legal here and yields a hydrator-less column.
    $column = HydratedColumn::make('orders.notes', 'Notes');

    expect(fn () => $column->getHydrator())
        ->toThrow(LogicException::class, 'HydratedColumn::for($hydrator, $field)');
});

test('reports the column type the hydrator declares', function () {
    // A Text-returning hydrator could not tell this from a hard-coded default.
    $column = HydratedColumn::for(new DateHydrator, 'Notes');

    expect($column->toArray()['type'])->toBe(ColumnType::Date->value);
});

test('resolves a hydrator named by class-string through the container', function () {
    $column = HydratedColumn::for(CountingHydrator::class, 'Notes');

    $hydrator = $column->getHydrator();

    expect($hydrator)->toBeInstanceOf(CountingHydrator::class)
        ->and($hydrator->probe)->toBeInstanceOf(HydratorProbe::class);
});

test('defers resolving a class-string until the hydrator is needed, then resolves once', function () {
    CountingHydrator::$constructed = 0;

    $column = HydratedColumn::for(CountingHydrator::class, 'Notes');

    // getColumns() runs on requests that never hydrate; declaring must not construct.
    expect(CountingHydrator::$constructed)->toBe(0);

    $first = $column->getHydrator();
    $second = $column->getHydrator();

    expect(CountingHydrator::$constructed)->toBe(1)
        ->and($first)->toBe($second);
});

test('holds an already-constructed hydrator as given', function () {
    $hydrator = new CountingHydrator(new HydratorProbe);
    CountingHydrator::$constructed = 0;

    $column = HydratedColumn::for($hydrator, 'Notes');

    expect($column->getHydrator())->toBe($hydrator)
        ->and(CountingHydrator::$constructed)->toBe(0);
});

test('resolves once for a whole page', function () {
    $hydrator = new StaticHydrator;

    HydratedColumn::for($hydrator, 'Notes')->hydrate(columnHydrationRows(range(1, 250)), 'column_ID');

    expect($hydrator->resolveCallCount)->toBe(1);
});

test('does not call the hydrator when there are no keys to look up', function () {
    $empty = new StaticHydrator;
    $allNull = new StaticHydrator;

    HydratedColumn::for($empty, 'Notes')->hydrate(columnHydrationRows([]), 'column_ID');
    HydratedColumn::for($allNull, 'Notes')->hydrate(columnHydrationRows([null, null]), 'column_ID');

    expect($empty->resolveCallCount)->toBe(0)
        ->and($allNull->resolveCallCount)->toBe(0);
});

test('hands over distinct keys with nulls dropped', function () {
    $hydrator = new StaticHydrator;

    HydratedColumn::for($hydrator, 'Notes')->hydrate(columnHydrationRows([3, 1, 3, null, 1, null]), 'column_ID');

    expect($hydrator->keysSeen[0]->all())->toBe([3, 1]);
});

test('writes the resolved value onto every row', function () {
    $rows = columnHydrationRows([1, 2]);

    HydratedColumn::for(new StaticHydrator([1 => 'first', 2 => 'second']), 'Notes')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Notes')->all())->toBe(['first', 'second']);
});

test('leaves a row null when its key is missing from the map', function () {
    $rows = columnHydrationRows([1, 99]);

    HydratedColumn::for(new StaticHydrator([1 => 'first']), 'Notes')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Notes')->all())->toBe(['first', null]);
});

test('leaves a row null when its own key is null', function () {
    $rows = columnHydrationRows([null]);

    HydratedColumn::for(new StaticHydrator([1 => 'first']), 'Notes')->hydrate($rows, 'column_ID');

    expect($rows->first()->column_Notes)->toBeNull();
});

test('lets an exception from the hydration source through', function () {
    $column = HydratedColumn::for(new StaticHydrator([], 'ID', throws: true), 'Notes');

    expect(fn () => $column->hydrate(columnHydrationRows([1]), 'column_ID'))
        ->toThrow(RuntimeException::class, 'the hydration source is down');
});

test('keeps keys apart that only compare loosely equal', function () {
    // '01' == 1 in PHP but indexes a different array bucket, so folding them loses a row's value.
    $hydrator = new StaticHydrator(['01' => 'padded', 1 => 'one']);
    $rows = columnHydrationRows(['01', 1]);

    HydratedColumn::for($hydrator, 'Notes')->hydrate($rows, 'column_ID');

    expect($hydrator->keysSeen[0]->all())->toBe(['01', 1])
        ->and($rows->pluck('column_Notes')->all())->toBe(['padded', 'one']);
});

test('throws when the keyed field holds something no array can be indexed by', function () {
    $column = HydratedColumn::for(new StaticHydrator, 'Notes');

    expect(fn () => $column->hydrate(columnHydrationRows([1.5]), 'column_ID'))
        ->toThrow(Exception::class, 'float');
});

test('throws rather than overwrite the field it keys on', function () {
    $hydrator = new StaticHydrator([], 'ID');

    expect(fn () => HydratedColumn::for($hydrator, 'ID')->hydrate(columnHydrationRows([1]), 'column_ID'))
        ->toThrow(Exception::class, 'collides')
        ->and($hydrator->resolveCallCount)->toBe(0);
});
