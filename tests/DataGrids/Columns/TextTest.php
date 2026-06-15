<?php

use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

test('text column initializes with correct data type', function () {
    $column = new Text('table.column', 'field');

    expect($column->toArray()['type'])->toBe(ColumnType::Text->value);
});

test('text column to array has correct structure', function () {
    $column = new Text('table.column', 'field');
    $array = $column->toArray();

    expect($array)->toHaveKeys(['field', 'type', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
    expect($array['field'])->toBe('column_field');
    expect($array['type'])->toBe(ColumnType::Text->value);
    expect($array['is_sortable'])->toBeTrue();
    expect($array['is_filterable'])->toBeTrue();
    expect($array['is_hidden'])->toBeFalse();
    expect($array['meta'])->toBeArray();
});

test('display format sets meta format', function () {
    $column = (new Text('table.column', 'field'))->displayFormat('uppercase');

    expect($column->toArray()['meta']['format'])->toBe('uppercase');
});
