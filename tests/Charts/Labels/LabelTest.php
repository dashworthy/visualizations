<?php

use Dashworthy\Visualizations\Charts\Labels\Label;

it('serializes to array with field, header, and meta', function () {
    $label = Label::make('order_date', 'date')->header('Order Date');

    expect($label->toArray())->toBe([
        'field' => 'label_date',
        'header' => 'Order Date',
        'meta' => [],
    ]);
});

it('defaults the header to the field name', function () {
    $label = Label::make('order_date', 'date');

    expect($label->toArray()['header'])->toBe('date');
});

it('stores and retrieves meta values', function () {
    $label = Label::make('order_date', 'date')
        ->meta('format', 'Y-m-d');

    expect($label->getMeta('format'))->toBe('Y-m-d');
    expect($label->toArray()['meta'])->toBe(['format' => 'Y-m-d']);
});

it('does not include a type key in the array', function () {
    $label = Label::make('order_date', 'date');

    expect($label->toArray())->not->toHaveKey('type');
});
