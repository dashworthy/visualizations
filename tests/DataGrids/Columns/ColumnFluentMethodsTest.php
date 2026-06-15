<?php

use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnPin;

test('without sorting disables sorting and returns self', function () {
    $column = new Text('table.name', 'Name');
    expect($column->toArray()['is_sortable'])->toBeTrue();

    $result = $column->withoutSorting();
    expect($column->toArray()['is_sortable'])->toBeFalse();
    expect($result)->toBe($column);
});

test('without filtering disables filtering and returns self', function () {
    $column = new Text('table.name', 'Name');
    expect($column->toArray()['is_filterable'])->toBeTrue();

    $result = $column->withoutFiltering();
    expect($column->toArray()['is_filterable'])->toBeFalse();
    expect($result)->toBe($column);
});

test('hidden marks column hidden and returns self', function () {
    $column = new Text('table.name', 'Name');
    expect($column->toArray()['is_hidden'])->toBeFalse();

    $result = $column->hidden();
    expect($column->toArray()['is_hidden'])->toBeTrue();
    expect($result)->toBe($column);
});

test('pin left sets left pin and returns self', function () {
    $column = new Text('table.name', 'Name');
    expect($column->toArray()['pin'])->toBe(ColumnPin::None->value);

    $result = $column->pinLeft();
    expect($column->toArray()['pin'])->toBe(ColumnPin::Left->value);
    expect($result)->toBe($column);
});

test('pin right sets right pin and returns self', function () {
    $column = new Text('table.name', 'Name');
    expect($column->toArray()['pin'])->toBe(ColumnPin::None->value);

    $result = $column->pinRight();
    expect($column->toArray()['pin'])->toBe(ColumnPin::Right->value);
    expect($result)->toBe($column);
});

test('as row key marks column as row key and returns self', function () {
    $column = new Text('table.name', 'Name');
    expect($column->toArray()['is_row_key'])->toBeFalse();

    $result = $column->asRowKey();
    expect($column->toArray()['is_row_key'])->toBeTrue();
    expect($result)->toBe($column);
});

test('fluent methods can be chained', function () {
    $column = (new Text('table.name', 'Name'))
        ->withoutSorting()
        ->withoutFiltering()
        ->hidden()
        ->pinLeft()
        ->asRowKey();

    $array = $column->toArray();

    expect($array['is_sortable'])->toBeFalse();
    expect($array['is_filterable'])->toBeFalse();
    expect($array['is_hidden'])->toBeTrue();
    expect($array['pin'])->toBe(ColumnPin::Left->value);
    expect($array['is_row_key'])->toBeTrue();
});

test('to array contains all expected keys', function () {
    $column = new Text('table.name', 'Name');

    expect($column->toArray())->toHaveKeys(['field', 'header', 'type', 'pin', 'is_row_key', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
});

test('without export marks column excluded from export and returns self', function () {
    $column = new Text('table.name', 'Name');
    expect($column->isExcludedFromExport())->toBeFalse();

    $result = $column->withoutExport();
    expect($column->isExcludedFromExport())->toBeTrue();
    expect($result)->toBe($column);
});

test('without export is not reflected in toArray', function () {
    $column = (new Text('table.name', 'Name'))->withoutExport();
    expect($column->toArray())->not->toHaveKey('is_excluded_from_export');
});
