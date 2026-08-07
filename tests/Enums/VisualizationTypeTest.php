<?php

use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Enums\VisualizationType;
use Dashworthy\Visualizations\Tests\Fixtures\Charts\RevenueChart;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\Metrics\RevenueMetric;

/*
 | A visualization reports its own kind so consumers do not have to guess it
 | with `instanceof` against all three base classes. The fixtures below declare
 | no type of their own — the whole point is that extending a base class is
 | enough — so these also pin that the answer is inherited.
 */

it('reports the kind of each visualization', function (VisualizationContract $visualization, VisualizationType $expected) {
    expect($visualization->getVisualizationType())->toBe($expected);
})->with([
    'grid' => [fn () => new UserDataGrid, VisualizationType::DataGrid],
    'chart' => [fn () => new RevenueChart, VisualizationType::Chart],
    'metric' => [fn () => new RevenueMetric, VisualizationType::Metric],
]);

/*
 | The values are the wire format — a consumer persists them or ships them to a
 | front-end — so they are pinned literally here. A rename is a breaking change
 | and this test is where that gets noticed.
 */
it('exposes stable string values', function () {
    expect(VisualizationType::DataGrid->value)->toBe('datagrid')
        ->and(VisualizationType::Chart->value)->toBe('chart')
        ->and(VisualizationType::Metric->value)->toBe('metric');
});
