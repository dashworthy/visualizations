<?php

use Dashworthy\Visualizations\DataGrids\Columns\Boolean;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

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
