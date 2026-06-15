<?php

use Dashworthy\Visualizations\Charts\Datasets\Scatter;

it('serializes to array with the scatter type', function () {
    $dataset = Scatter::make('SUM(total)', 'revenue')->header('Revenue');

    expect($dataset->toArray())->toBe([
        'field' => 'dataset_revenue',
        'header' => 'Revenue',
        'type' => 'scatter',
        'meta' => [],
    ]);
});
