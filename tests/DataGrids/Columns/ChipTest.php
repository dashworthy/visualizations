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

/*
 | severityUsing() defers the severity map to serialisation. severity() takes
 | its map eagerly, so a map read from the database is built by every caller of
 | a grid's getColumns() — including the data path, which fetches rows on every
 | load, sort, filter and page but never serialises the column. Deferring the
 | map moves that work to toArray(), the one place it is emitted.
 |
 | The closure counts its own calls so a chip that resolved eagerly — or never —
 | fails here regardless of what any grid asks the database.
 */
function countingChip(int &$calls, array $severity = ['active' => Severity::SUCCESS]): Chip
{
    return Chip::make('table.status', 'Status')
        ->severityUsing(function () use (&$calls, $severity): array {
            $calls++;

            return $severity;
        });
}

test('severityUsing does not resolve the map when the column is declared', function () {
    $calls = 0;

    countingChip($calls);

    expect($calls)->toBe(0);
});

test('severityUsing does not resolve the map for the accessors the data path reads', function () {
    $calls = 0;
    $chip = countingChip($calls);

    $chip->getField();
    $chip->getHeader();
    $chip->getSelectWith();
    $chip->getSelectWithBindings();

    expect($calls)->toBe(0);
});

test('severityUsing resolves the map when the column is serialised', function () {
    $calls = 0;
    $chip = countingChip($calls, ['active' => Severity::SUCCESS, 'inactive' => Severity::DANGER]);

    $payload = $chip->toArray();

    expect($calls)->toBe(1)
        ->and($payload['meta']['severity'])
        ->toBe(['active' => Severity::SUCCESS, 'inactive' => Severity::DANGER]);
});

/*
 | The contract is "once per serialisation", not "once ever": there is no memo,
 | because a memo on the instance would never be read a second time — a grid
 | builds a fresh column per request — and a static one would outlive a test's
 | database rollback.
 */
test('severityUsing resolves the map once per serialisation', function () {
    $calls = 0;
    $chip = countingChip($calls);

    $chip->toArray();
    $chip->toArray();
    $chip->toArray();

    expect($calls)->toBe(3);
});

test('severityUsing keeps the rest of the column payload while deferring the map', function () {
    $calls = 0;
    $chip = countingChip($calls)->defaultsToInfoSeverity();

    $payload = $chip->toArray();

    expect($payload['header'])->toBe('Status')
        ->and($payload['field'])->toBe('column_Status')
        ->and($payload['is_sortable'])->toBeTrue()
        ->and($payload['meta']['default_severity'])->toBe(Severity::INFO)
        ->and($payload['meta']['severity'])->toBe(['active' => Severity::SUCCESS]);
});

test('a chip with no deferred map serialises without a severity key', function () {
    $payload = Chip::make('table.status', 'Status')->toArray();

    expect($payload['meta'])->not->toHaveKey('severity')
        ->and($payload['type'])->toBe(ColumnType::Chip->value);
});

/*
 | When both are set the deferred map wins: it is applied at serialisation and
 | overwrites whatever the eager call left behind. Pinned rather than left to
 | chance, since the two would otherwise disagree silently about which map the
 | frontend receives.
 */
test('severityUsing overrides an eager severity map', function () {
    $calls = 0;

    $payload = countingChip($calls, ['active' => Severity::SUCCESS])
        ->severity(['active' => Severity::SECONDARY])
        ->toArray();

    expect($payload['meta']['severity'])->toBe(['active' => Severity::SUCCESS]);
});

test('severityUsing returns the chip so it can be chained', function () {
    $chip = Chip::make('table.status', 'Status');

    expect($chip->severityUsing(fn (): array => []))->toBe($chip);
});
