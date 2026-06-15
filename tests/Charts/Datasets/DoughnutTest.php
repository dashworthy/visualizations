<?php

use Dashworthy\Visualizations\Charts\Datasets\Doughnut;

it('serializes to array with the doughnut type', function () {
    $dataset = Doughnut::make('SUM(total)', 'revenue')->header('Revenue');

    expect($dataset->toArray())->toBe([
        'field' => 'dataset_revenue',
        'header' => 'Revenue',
        'type' => 'doughnut',
        'meta' => [],
    ]);
});

it('stores cutout percentage in meta', function () {
    $dataset = Doughnut::make('SUM(total)', 'revenue')->cutout('75%');

    expect($dataset->getMeta('cutout'))->toBe('75%');
    expect($dataset->toArray()['meta'])->toBe(['cutout' => '75%']);
});
