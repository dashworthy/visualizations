<?php

use Dashworthy\Visualizations\DataGrids\Columns\Boolean;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;

test('boolean column initializes with correct data type', function () {
    $column = new Boolean('table.column', 'field');

    expect($column->toArray()['type'])->toBe(ColumnType::Boolean->value);
});

test('boolean column to array has correct structure', function () {
    $column = new Boolean('table.column', 'field');
    $array = $column->toArray();

    expect($array)->toHaveKeys(['field', 'type', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
    expect($array['field'])->toBe('column_field');
    expect($array['type'])->toBe(ColumnType::Boolean->value);
    expect($array['is_sortable'])->toBeTrue();
    expect($array['is_filterable'])->toBeTrue();
    expect($array['is_hidden'])->toBeFalse();
    expect($array['meta'])->toBeArray();
});

test('get select as returns correct value', function () {
    $column = new Boolean('table.column', 'field');

    expect($column->getSelectWith())->toBe('table.column');
});

test('display format sets truthy and falsy meta', function () {
    $column = (new Boolean('table.column', 'field'))->displayFormat('Yes', 'No');

    expect($column->toArray()['meta']['format'])->toEqual([
        'truthy' => 'Yes',
        'falsy' => 'No',
    ]);
});

test('hydrated boolean column keeps its type and selects nothing', function () {
    $column = Boolean::make(new StaticHydrator, 'Has Notes');

    expect($column->toArray())->toMatchArray([
        'type' => ColumnType::Boolean->value,
        'is_sortable' => false,
        'is_filterable' => false,
    ])->and($column->hasExpression())->toBeFalse();
});

test('hydrated boolean column accepts a hydrator class-string', function () {
    $column = Boolean::make(CountingHydrator::class, 'Has Notes');

    expect($column->toArray()['type'])->toBe(ColumnType::Boolean->value)
        ->and($column->getHydrator())->toBeInstanceOf(CountingHydrator::class);
});

test('hydrated boolean column keeps its display format', function () {
    $column = Boolean::make(new StaticHydrator, 'Has Notes')->displayFormat('Yes', 'No');

    expect($column->toArray()['meta']['format'])->toBe(['truthy' => 'Yes', 'falsy' => 'No']);
});

test('hydrated boolean column fills a page of rows', function () {
    $rows = collect([(object) ['column_ID' => 1], (object) ['column_ID' => 2]]);

    Boolean::make(new StaticHydrator([1 => true, 2 => false]), 'Has Notes')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Has Notes')->all())->toBe([true, false]);
});
