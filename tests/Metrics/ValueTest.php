<?php

use Dashworthy\Visualizations\Metrics\Value;

it('makes a Value with an expression and field', function () {
    $value = Value::make('sum(orders.total)', 'revenue');

    expect($value->getField())->toBe('value_revenue');
    expect($value->getSelectWith())->toBe('sum(orders.total)');
});

it('uses the field as the default header', function () {
    $value = Value::make('sum(orders.total)', 'revenue');

    expect($value->getHeader())->toBe('revenue');
});

it('uses the provided header when set', function () {
    $value = Value::make('sum(orders.total)', 'revenue')
        ->header('Total Revenue');

    expect($value->getHeader())->toBe('Total Revenue');
});

it('serialises to an array', function () {
    $value = Value::make('sum(orders.total)', 'revenue')
        ->header('Total Revenue');

    expect($value->toArray())->toBe([
        'field' => 'value_revenue',
        'header' => 'Total Revenue',
        'meta' => [],
    ]);
});

it('stores and retrieves meta values', function () {
    $value = Value::make('sum(orders.total)', 'revenue')
        ->meta('currency', 'USD');

    expect($value->getMeta('currency'))->toBe('USD');
    expect($value->toArray()['meta'])->toBe(['currency' => 'USD']);
});

it('does not include a type key in the array', function () {
    $value = Value::make('sum(orders.total)', 'revenue');

    expect($value->toArray())->not->toHaveKey('type');
});
