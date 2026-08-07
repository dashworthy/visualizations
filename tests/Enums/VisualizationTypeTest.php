<?php

use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;
use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\Enums\VisualizationType;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Tests\Fixtures\Charts\RevenueChart;
use Dashworthy\Visualizations\Tests\Fixtures\CustomVisualizationType;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\FunnelDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\Metrics\RevenueMetric;

/*
 | A visualization reports its own kind so consumers do not have to guess it
 | with `instanceof` against every base class. The fixtures below declare no
 | type of their own — extending a base class is meant to be enough — so these
 | also pin that the answer is inherited.
 */

it('reports the kind of each visualization it ships', function (VisualizationContract $visualization, VisualizationType $expected) {
    expect($visualization->getVisualizationType())->toBe($expected);
})->with([
    'grid' => [fn () => new UserDataGrid, VisualizationType::DataGrid],
    'chart' => [fn () => new RevenueChart, VisualizationType::Chart],
    'metric' => [fn () => new RevenueMetric, VisualizationType::Metric],
]);

/*
 | The set of kinds is open. These are the tests that fail if it closes again —
 | if the contract's return type narrows to the enum, or a base class narrows
 | its own, `FunnelDataGrid` stops being loadable at all.
 */
it('accepts a kind the package does not define', function () {
    $funnel = new FunnelDataGrid;

    expect($funnel->getVisualizationType())->toBe(CustomVisualizationType::Funnel)
        ->and($funnel->getVisualizationType())->toBeInstanceOf(DefinesVisualizationType::class)
        ->and($funnel->getVisualizationType()->getTypeKey())->toBe('funnel');
});

it('types the accessor as the contract so consumers can widen the set', function (string $class) {
    $returnType = (new ReflectionMethod($class, 'getVisualizationType'))->getReturnType();

    expect($returnType)->toBeInstanceOf(ReflectionNamedType::class)
        ->and($returnType->getName())->toBe(DefinesVisualizationType::class);
})->with([
    'contract' => VisualizationContract::class,
    'grid' => DataGrid::class,
    'chart' => Chart::class,
    'metric' => Metric::class,
]);

/*
 | The keys are the wire format — a consumer persists them or ships them to a
 | front-end — so they are pinned literally. Renaming one is a breaking change
 | and this is where that gets noticed.
 */
it('exposes stable keys', function () {
    expect(VisualizationType::DataGrid->getTypeKey())->toBe('datagrid')
        ->and(VisualizationType::Chart->getTypeKey())->toBe('chart')
        ->and(VisualizationType::Metric->getTypeKey())->toBe('metric');
});
