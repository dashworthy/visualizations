<?php

use Dashworthy\Visualizations\DataGrids\Enums\ColumnPin;

test('none is not pinned', function () {
    expect(ColumnPin::None->isPinned())->toBeFalse();
});

test('left is pinned', function () {
    expect(ColumnPin::Left->isPinned())->toBeTrue();
});

test('right is pinned', function () {
    expect(ColumnPin::Right->isPinned())->toBeTrue();
});

test('is left', function () {
    expect(ColumnPin::Left->isLeft())->toBeTrue();
    expect(ColumnPin::Right->isLeft())->toBeFalse();
    expect(ColumnPin::None->isLeft())->toBeFalse();
});

test('is right', function () {
    expect(ColumnPin::Right->isRight())->toBeTrue();
    expect(ColumnPin::Left->isRight())->toBeFalse();
    expect(ColumnPin::None->isRight())->toBeFalse();
});

test('values', function () {
    expect(ColumnPin::None->value)->toBe('none');
    expect(ColumnPin::Left->value)->toBe('left');
    expect(ColumnPin::Right->value)->toBe('right');
});
