<?php

use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

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
