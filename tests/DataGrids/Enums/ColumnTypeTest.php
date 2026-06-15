<?php

use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;

test('all expected cases exist', function () {
    $expectedCases = ['Text', 'Number', 'Date', 'Time', 'DateTime', 'Boolean', 'Point', 'Chip'];

    foreach ($expectedCases as $case) {
        expect(ColumnType::tryFrom(strtolower($case)))->not->toBeNull("Expected ColumnType case: $case");
    }
});

test('values', function () {
    expect(ColumnType::Text->value)->toBe('text');
    expect(ColumnType::Number->value)->toBe('number');
    expect(ColumnType::Date->value)->toBe('date');
    expect(ColumnType::Time->value)->toBe('time');
    expect(ColumnType::DateTime->value)->toBe('datetime');
    expect(ColumnType::Boolean->value)->toBe('boolean');
    expect(ColumnType::Point->value)->toBe('point');
    expect(ColumnType::Chip->value)->toBe('chip');
});
