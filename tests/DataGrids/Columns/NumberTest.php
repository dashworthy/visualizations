<?php

use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;

test('number column initializes with correct data type', function () {
    $column = new Number('table.column', 'field');

    expect($column->toArray()['type'])->toBe(ColumnType::Number->value);
});

test('number column to array has correct structure', function () {
    $column = new Number('table.column', 'field');
    $array = $column->toArray();

    expect($array)->toHaveKeys(['field', 'type', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
    expect($array['field'])->toBe('column_field');
    expect($array['type'])->toBe(ColumnType::Number->value);
    expect($array['is_sortable'])->toBeTrue();
    expect($array['is_filterable'])->toBeTrue();
    expect($array['is_hidden'])->toBeFalse();
    expect($array['meta'])->toBeArray();
});

test('display format sets meta format', function () {
    $column = (new Number('table.column', 'field'))->displayFormat('#,##0.00');

    expect($column->toArray()['meta']['format'])->toBe('#,##0.00');
});

test('hydrated number column keeps its type and selects nothing', function () {
    $column = Number::make(new StaticHydrator, 'Orders');

    expect($column->toArray())->toMatchArray([
        'type' => ColumnType::Number->value,
        'is_sortable' => false,
        'is_filterable' => false,
    ])->and($column->hasExpression())->toBeFalse();
});

test('hydrated number column accepts a hydrator class-string', function () {
    $column = Number::make(CountingHydrator::class, 'Orders');

    expect($column->toArray()['type'])->toBe(ColumnType::Number->value)
        ->and($column->getHydrator())->toBeInstanceOf(CountingHydrator::class);
});

test('hydrated number column keeps its display format', function () {
    $column = Number::make(new StaticHydrator, 'Orders')->displayFormat('0,0');

    expect($column->toArray()['meta']['format'])->toBe('0,0');
});

test('hydrated number column fills a page of rows', function () {
    $rows = collect([(object) ['column_ID' => 1], (object) ['column_ID' => 2]]);

    Number::make(new StaticHydrator([1 => 10, 2 => 20]), 'Orders')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Orders')->all())->toBe([10, 20]);
});
