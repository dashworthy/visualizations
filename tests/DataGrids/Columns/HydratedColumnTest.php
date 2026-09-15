<?php

use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DateHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\HydratorProbe;

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
