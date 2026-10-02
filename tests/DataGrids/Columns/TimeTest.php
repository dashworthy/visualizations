<?php

use Dashworthy\Visualizations\DataGrids\Columns\Time;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;

test('time column initializes with correct data type', function () {
    $column = new Time('table.column', 'name');

    expect($column->toArray()['type'])->toBe(ColumnType::Time->value);
});

test('time column to array has correct structure', function () {
    $column = new Time('table.column', 'field');
    $array = $column->toArray();

    expect($array)->toHaveKeys(['field', 'type', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
    expect($array['field'])->toBe('column_field');
    expect($array['type'])->toBe(ColumnType::Time->value);
    expect($array['is_sortable'])->toBeTrue();
    expect($array['is_filterable'])->toBeTrue();
    expect($array['is_hidden'])->toBeFalse();
    expect($array['meta'])->toBeArray();
});

test('display format sets meta format', function () {
    $column = (new Time('table.column', 'field'))->displayFormat('H:i:s');

    expect($column->toArray()['meta']['format'])->toBe('H:i:s');
});

test('hydrated time column keeps its type and selects nothing', function () {
    $column = Time::make(new StaticHydrator, 'Shift Start');

    expect($column->toArray())->toMatchArray([
        'type' => ColumnType::Time->value,
        'is_sortable' => false,
        'is_filterable' => false,
    ])->and($column->hasExpression())->toBeFalse();
});

test('hydrated time column accepts a hydrator class-string', function () {
    $column = Time::make(CountingHydrator::class, 'Shift Start');

    expect($column->toArray()['type'])->toBe(ColumnType::Time->value)
        ->and($column->getHydrator())->toBeInstanceOf(CountingHydrator::class);
});

test('hydrated time column keeps its display format', function () {
    $column = Time::make(new StaticHydrator, 'Shift Start')->displayFormat('H:i');

    expect($column->toArray()['meta']['format'])->toBe('H:i');
});

test('hydrated time column fills a page of rows', function () {
    $rows = collect([(object) ['column_ID' => 1]]);

    Time::make(new StaticHydrator([1 => '09:30:00']), 'Shift Start')->hydrate($rows, 'column_ID');

    expect($rows->first()->{'column_Shift Start'})->toBe('09:30:00');
});
