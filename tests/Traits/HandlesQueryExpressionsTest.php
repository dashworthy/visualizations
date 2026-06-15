<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;

class MockVisualizable extends Visualizable
{
    public function getFieldPrefix(): string
    {
        return 'mock_';
    }

    public function toArray(): array
    {
        return [];
    }
}

test('can add and get bindings', function () {
    $mock = MockVisualizable::make('test_column', 'test_field');
    $mock->addSelectWithBinding('test_binding');
    expect($mock->getSelectWithBindings())->toContain('test_binding');
});

test('can get field', function () {
    $mock = MockVisualizable::make('test_column', 'test_field');
    expect($mock->getField())->toBe('mock_test_field');
});

test('can check if having is required', function () {
    $mock = new MockVisualizable('COUNT(test_column)', 'test_field');
    expect($mock->isHavingRequired())->toBeTrue();

    $mock = MockVisualizable::make('test_column', 'test_field');
    expect($mock->isHavingRequired())->toBeFalse();
});

test('can be created with make method', function () {
    $instance = MockVisualizable::make('test_column', 'test_field', ['test_binding']);

    expect($instance->getSelectWith())->toBe('test_column');
    expect($instance->getField())->toBe('mock_test_field');
    expect($instance->getSelectWithBindings())->toContain('test_binding');
});
