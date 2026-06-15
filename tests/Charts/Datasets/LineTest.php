<?php

use Dashworthy\Visualizations\Charts\Datasets\Line;

it('serializes to array with the line type', function () {
    $dataset = Line::make('SUM(total)', 'revenue')->header('Revenue');

    expect($dataset->toArray())->toBe([
        'field' => 'dataset_revenue',
        'header' => 'Revenue',
        'type' => 'line',
        'meta' => [],
    ]);
});

it('defaults the header to the field name', function () {
    $dataset = Line::make('SUM(total)', 'revenue');

    expect($dataset->toArray()['header'])->toBe('revenue');
});

it('stores tension in meta', function () {
    $dataset = Line::make('SUM(total)', 'revenue')->tension(0.4);

    expect($dataset->getMeta('tension'))->toBe(0.4);
    expect($dataset->toArray()['meta'])->toBe(['tension' => 0.4]);
});

it('stores fill in meta when filled', function () {
    $dataset = Line::make('SUM(total)', 'revenue')->filled();

    expect($dataset->getMeta('fill'))->toBe(true);
    expect($dataset->toArray()['meta'])->toBe(['fill' => true]);
});

it('supports chaining tension and filled together', function () {
    $dataset = Line::make('SUM(total)', 'revenue')
        ->tension(0.3)
        ->filled();

    expect($dataset->toArray()['meta'])->toBe([
        'tension' => 0.3,
        'fill' => true,
    ]);
});
