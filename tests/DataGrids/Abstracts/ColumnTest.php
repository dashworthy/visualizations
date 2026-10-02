<?php

use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\HydratorProbe;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;
use Illuminate\Support\Collection;

/** Rows as they arrive from the query builder: plain objects keyed by prefixed field. */
function columnRows(array $ids): Collection
{
    return collect($ids)->map(fn ($id) => (object) ['column_ID' => $id, 'column_Name' => 'n']);
}

test('a column declared with SQL carries an expression and no hydrator', function () {
    $column = Text::make('users.notes', 'Notes');

    expect($column->hasExpression())->toBeTrue()
        ->and(fn () => $column->getHydrator())->toThrow(LogicException::class, 'was declared with SQL');
});

test('a column declared with a hydrator carries no expression', function () {
    // What the query generator branches on to leave it out of the statement.
    $column = Text::make(new StaticHydrator, 'Notes');

    expect($column->hasExpression())->toBeFalse()
        ->and($column->getSelectWith())->toBe('')
        ->and($column->getSelectWithBindings())->toBe([]);
});

test('a hydrated column is never sortable or filterable', function () {
    // Column defaults both to true, so this fails if useHydrator() stops switching them off.
    expect(Text::make(new StaticHydrator, 'Notes')->toArray())
        ->toMatchArray(['is_sortable' => false, 'is_filterable' => false]);
});

test('resolves a hydrator named by class-string through the container', function () {
    $hydrator = Text::make(CountingHydrator::class, 'Notes')->getHydrator();

    expect($hydrator)->toBeInstanceOf(CountingHydrator::class)
        ->and($hydrator->probe)->toBeInstanceOf(HydratorProbe::class);
});

test('defers resolving a class-string until the hydrator is needed, then resolves once', function () {
    CountingHydrator::$constructed = 0;

    $column = Text::make(CountingHydrator::class, 'Notes');

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

    expect(Text::make($hydrator, 'Notes')->getHydrator())->toBe($hydrator)
        ->and(CountingHydrator::$constructed)->toBe(0);
});

test('resolves once for a whole page', function () {
    $hydrator = new StaticHydrator;

    Text::make($hydrator, 'Notes')->hydrate(columnRows(range(1, 250)), 'column_ID');

    expect($hydrator->resolveCallCount)->toBe(1);
});

test('does not call the hydrator when there are no keys to look up', function () {
    $empty = new StaticHydrator;
    $allNull = new StaticHydrator;

    Text::make($empty, 'Notes')->hydrate(columnRows([]), 'column_ID');
    Text::make($allNull, 'Notes')->hydrate(columnRows([null, null]), 'column_ID');

    expect($empty->resolveCallCount)->toBe(0)
        ->and($allNull->resolveCallCount)->toBe(0);
});

test('hands over distinct keys with nulls dropped', function () {
    $hydrator = new StaticHydrator;

    Text::make($hydrator, 'Notes')->hydrate(columnRows([3, 1, 3, null, 1, null]), 'column_ID');

    expect($hydrator->keysSeen[0]->all())->toBe([3, 1]);
});

test('writes the resolved value onto every row', function () {
    $rows = columnRows([1, 2]);

    Text::make(new StaticHydrator([1 => 'first', 2 => 'second']), 'Notes')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Notes')->all())->toBe(['first', 'second']);
});

test('leaves a row null when its key is missing from the map', function () {
    $rows = columnRows([1, 99]);

    Text::make(new StaticHydrator([1 => 'first']), 'Notes')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Notes')->all())->toBe(['first', null]);
});

test('leaves a row null when its own key is null', function () {
    $rows = columnRows([null]);

    Text::make(new StaticHydrator([1 => 'first']), 'Notes')->hydrate($rows, 'column_ID');

    expect($rows->first()->column_Notes)->toBeNull();
});

test('lets an exception from the hydration source through', function () {
    $column = Text::make(new StaticHydrator([], 'ID', throws: true), 'Notes');

    expect(fn () => $column->hydrate(columnRows([1]), 'column_ID'))
        ->toThrow(RuntimeException::class, 'the hydration source is down');
});

test('keeps keys apart that only compare loosely equal', function () {
    // '01' == 1 in PHP but indexes a different array bucket, so folding them loses a row's value.
    $hydrator = new StaticHydrator(['01' => 'padded', 1 => 'one']);
    $rows = columnRows(['01', 1]);

    Text::make($hydrator, 'Notes')->hydrate($rows, 'column_ID');

    expect($hydrator->keysSeen[0]->all())->toBe(['01', 1])
        ->and($rows->pluck('column_Notes')->all())->toBe(['padded', 'one']);
});

test('throws when the keyed field holds something no array can be indexed by', function () {
    $column = Text::make(new StaticHydrator, 'Notes');

    expect(fn () => $column->hydrate(columnRows([1.5]), 'column_ID'))
        ->toThrow(Exception::class, 'float');
});

test('throws rather than overwrite the field it keys on', function () {
    $hydrator = new StaticHydrator([], 'ID');

    expect(fn () => Text::make($hydrator, 'ID')->hydrate(columnRows([1]), 'column_ID'))
        ->toThrow(Exception::class, 'collides')
        ->and($hydrator->resolveCallCount)->toBe(0);
});
