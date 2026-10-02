<?php

use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;

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

test('hydrated text column keeps its type and selects nothing', function () {
    $column = Text::make(new StaticHydrator, 'Notes');

    expect($column->toArray())->toMatchArray([
        'type' => ColumnType::Text->value,
        'is_sortable' => false,
        'is_filterable' => false,
    ])->and($column->hasExpression())->toBeFalse();
});

test('hydrated text column accepts a hydrator class-string', function () {
    $column = Text::make(CountingHydrator::class, 'Notes');

    expect($column->toArray()['type'])->toBe(ColumnType::Text->value)
        ->and($column->getHydrator())->toBeInstanceOf(CountingHydrator::class);
});

test('hydrated text column keeps its display format', function () {
    $column = Text::make(new StaticHydrator, 'Notes')->displayFormat('uppercase');

    expect($column->toArray()['meta']['format'])->toBe('uppercase');
});

test('hydrated text column fills a page of rows', function () {
    $rows = collect([(object) ['column_ID' => 1], (object) ['column_ID' => 2]]);

    Text::make(new StaticHydrator([1 => 'one', 2 => 'two']), 'Notes')->hydrate($rows, 'column_ID');

    expect($rows->pluck('column_Notes')->all())->toBe(['one', 'two']);
});
