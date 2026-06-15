<?php

use Dashworthy\Visualizations\DataGrids\Columns\Time;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

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
