<?php

use Dashworthy\Visualizations\Charts\Datasets\Area;

it('serializes to array with the area type', function () {
    $dataset = Area::make('SUM(total)', 'revenue')->header('Revenue');

    expect($dataset->toArray())->toBe([
        'field' => 'dataset_revenue',
        'header' => 'Revenue',
        'type' => 'area',
        'meta' => [],
    ]);
});

it('stores tension in meta', function () {
    $dataset = Area::make('SUM(total)', 'revenue')->tension(0.5);

    expect($dataset->getMeta('tension'))->toBe(0.5);
    expect($dataset->toArray()['meta'])->toBe(['tension' => 0.5]);
});
