<?php

use Dashworthy\Visualizations\DataGrids\Columns\Chip;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\DataGrids\Enums\Severity;

test('chip column initializes with correct data type', function () {
    $column = new Chip('table.status', 'Status');

    expect($column->toArray()['type'])->toBe(ColumnType::Chip->value);
});

test('chip column to array has correct structure', function () {
    $column = new Chip('table.status', 'Status');
    $array = $column->toArray();

    expect($array)->toHaveKeys(['field', 'type', 'is_sortable', 'is_filterable', 'is_hidden', 'meta']);
    expect($array['field'])->toBe('column_Status');
    expect($array['type'])->toBe(ColumnType::Chip->value);
    expect($array['is_sortable'])->toBeTrue();
    expect($array['is_filterable'])->toBeTrue();
    expect($array['is_hidden'])->toBeFalse();
    expect($array['meta'])->toBeArray();
});

test('severity sets meta', function () {
    $severityMap = [
        'active' => Severity::SUCCESS,
        'inactive' => Severity::DANGER,
    ];

    $column = (new Chip('table.status', 'Status'))->severity($severityMap);

    expect($column->toArray()['meta']['severity'])->toEqual($severityMap);
});

test('with default severity', function () {
    $column = (new Chip('table.status', 'Status'))->withDefaultSeverity(Severity::INFO);

    expect($column->toArray()['meta']['default_severity'])->toBe(Severity::INFO);
});

test('defaults to info severity', function () {
    $column = (new Chip('table.status', 'Status'))->defaultsToInfoSeverity();

    expect($column->toArray()['meta']['default_severity'])->toBe(Severity::INFO);
});

test('defaults to warning severity', function () {
    $column = (new Chip('table.status', 'Status'))->defaultsToWarningSeverity();

    expect($column->toArray()['meta']['default_severity'])->toBe(Severity::WARNING);
});

test('defaults to danger severity', function () {
    $column = (new Chip('table.status', 'Status'))->defaultsToDangerSeverity();

    expect($column->toArray()['meta']['default_severity'])->toBe(Severity::DANGER);
});

test('defaults to primary severity', function () {
    $column = (new Chip('table.status', 'Status'))->defaultsToPrimarySeverity();

    expect($column->toArray()['meta']['default_severity'])->toBe(Severity::PRIMARY);
});

test('defaults to secondary severity', function () {
    $column = (new Chip('table.status', 'Status'))->defaultsToSecondarySeverity();

    expect($column->toArray()['meta']['default_severity'])->toBe(Severity::SECONDARY);
});

test('defaults to contrast severity', function () {
    $column = (new Chip('table.status', 'Status'))->defaultsToContrastSeverity();

    expect($column->toArray()['meta']['default_severity'])->toBe(Severity::CONTRAST);
});

test('severity and default severity together', function () {
    $severityMap = [
        'active' => Severity::SUCCESS,
        'inactive' => Severity::DANGER,
    ];

    $column = (new Chip('table.status', 'Status'))
        ->severity($severityMap)
        ->defaultsToInfoSeverity();

    $array = $column->toArray();

    expect($array['meta']['severity'])->toEqual($severityMap);
    expect($array['meta']['default_severity'])->toBe(Severity::INFO);
});
