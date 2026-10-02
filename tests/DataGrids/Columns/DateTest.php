<?php

use Dashworthy\Visualizations\DataGrids\Columns\Date;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DateHydrator;

test('date column initializes with correct data type', function () {
    $column = new Date('table.column', 'field');

    expect($column->toArray()['type'])->toBe(ColumnType::Date->value);
});

test('date column to array has correct structure', function () {
    $column = new Date('table.column', 'field');
    $array = $column->toArray();

    expect($array)->toHaveKeys(['field', 'type', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
    expect($array['field'])->toBe('column_field');
    expect($array['type'])->toBe(ColumnType::Date->value);
    expect($array['is_sortable'])->toBeTrue();
    expect($array['is_filterable'])->toBeTrue();
    expect($array['is_hidden'])->toBeFalse();
    expect($array['meta'])->toBeArray();
});

test('display format sets meta format', function () {
    $column = (new Date('table.column', 'field'))->displayFormat('Y-m-d');

    expect($column->toArray()['meta']['format'])->toBe('Y-m-d');
});

test('hydrated date column keeps its type and selects nothing', function () {
    $column = Date::make(new DateHydrator, 'Last Order');

    expect($column->toArray())->toMatchArray([
        'type' => ColumnType::Date->value,
        'is_sortable' => false,
        'is_filterable' => false,
    ])->and($column->hasExpression())->toBeFalse();
});

test('hydrated date column accepts a hydrator class-string', function () {
    $column = Date::make(CountingHydrator::class, 'Last Order');

    expect($column->toArray()['type'])->toBe(ColumnType::Date->value)
        ->and($column->getHydrator())->toBeInstanceOf(CountingHydrator::class);
});

test('hydrated date column keeps its display format', function () {
    $column = Date::make(new DateHydrator, 'Last Order')->displayFormat('Y-m-d');

    expect($column->toArray()['meta']['format'])->toBe('Y-m-d');
});

test('hydrated date column fills a page of rows', function () {
    $rows = collect([(object) ['column_ID' => 1], (object) ['column_ID' => 2]]);

    Date::make(new DateHydrator, 'Last Order')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Last Order')->all())->toBe(['2026-10-02', '2026-10-02']);
});
