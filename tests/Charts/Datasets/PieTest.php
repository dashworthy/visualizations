<?php

use Dashworthy\Visualizations\Charts\Datasets\Pie;

it('serializes to array with the pie type', function () {
    $dataset = Pie::make('SUM(total)', 'revenue')->header('Revenue');

    expect($dataset->toArray())->toBe([
        'field' => 'dataset_revenue',
        'header' => 'Revenue',
        'type' => 'pie',
        'meta' => [],
    ]);
});
