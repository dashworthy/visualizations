<?php

use Dashworthy\Visualizations\DataGrids\Enums\Severity;

test('all expected cases exist', function () {
    $expectedCases = ['SUCCESS', 'INFO', 'WARNING', 'DANGER', 'PRIMARY', 'SECONDARY', 'CONTRAST'];

    foreach ($expectedCases as $case) {
        expect(Severity::tryFrom(strtolower($case)))->not->toBeNull("Expected Severity case: $case");
    }
});

test('values', function () {
    expect(Severity::SUCCESS->value)->toBe('success');
    expect(Severity::INFO->value)->toBe('info');
    expect(Severity::WARNING->value)->toBe('warning');
    expect(Severity::DANGER->value)->toBe('danger');
    expect(Severity::PRIMARY->value)->toBe('primary');
    expect(Severity::SECONDARY->value)->toBe('secondary');
    expect(Severity::CONTRAST->value)->toBe('contrast');
});
