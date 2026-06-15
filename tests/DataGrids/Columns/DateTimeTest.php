<?php

use Dashworthy\Visualizations\DataGrids\Columns\DateTime;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

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
