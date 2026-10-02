<?php

use Dashworthy\Visualizations\DataGrids\Columns\DateTime;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DateHydrator;

test('datetime column initializes with correct data type', function () {
    $column = new DateTime('table.column', 'field');

    expect($column->toArray()['type'])->toBe(ColumnType::DateTime->value);
});

test('datetime column to array has correct structure', function () {
    $column = new DateTime('table.column', 'field');
    $array = $column->toArray();

    expect($array)->toHaveKeys(['field', 'type', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
    expect($array['field'])->toBe('column_field');
    expect($array['type'])->toBe(ColumnType::DateTime->value);
    expect($array['is_sortable'])->toBeTrue();
    expect($array['is_filterable'])->toBeTrue();
    expect($array['is_hidden'])->toBeFalse();
    expect($array['meta'])->toBeArray();
});

test('display format sets meta format', function () {
    $column = (new DateTime('table.column', 'field'))->displayFormat('Y-m-d H:i:s');

    expect($column->toArray()['meta']['format'])->toBe('Y-m-d H:i:s');
});

test('hydrated date-time column keeps its type and selects nothing', function () {
    $column = DateTime::make(new DateHydrator, 'Last Login');

    expect($column->toArray())->toMatchArray([
        'type' => ColumnType::DateTime->value,
        'is_sortable' => false,
        'is_filterable' => false,
    ])->and($column->hasExpression())->toBeFalse();
});

test('hydrated date-time column accepts a hydrator class-string', function () {
    $column = DateTime::make(CountingHydrator::class, 'Last Login');

    expect($column->toArray()['type'])->toBe(ColumnType::DateTime->value)
        ->and($column->getHydrator())->toBeInstanceOf(CountingHydrator::class);
});

test('hydrated date-time column keeps its display format', function () {
    $column = DateTime::make(new DateHydrator, 'Last Login')->displayFormat('Y-m-d H:i');

    expect($column->toArray()['meta']['format'])->toBe('Y-m-d H:i');
});

test('hydrated date-time column fills a page of rows', function () {
    $rows = collect([(object) ['column_ID' => 1]]);

    DateTime::make(new DateHydrator, 'Last Login')->hydrate($rows, 'column_ID');

    expect($rows->first()->{'column_Last Login'})->toBe('2026-10-02');
});
