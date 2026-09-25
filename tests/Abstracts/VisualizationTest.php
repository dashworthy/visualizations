<?php

use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\FunnelDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\Funnels\SalesFunnel;
use Illuminate\Support\Facades\Event;

it('derives route name, path and key for a kind the package does not ship', function () {
    $funnel = new SalesFunnel;

    expect($funnel->getRouteName())->toBe('funnels.sales')
        ->and($funnel->getRoutePath())->toBe('funnels/sales')
        ->and($funnel->getVisualizationKey())->toBe('funnels.sales');
});

it('builds the schema around the kind specific body', function () {
    $schema = SalesFunnel::schema();

    expect(array_keys($schema))->toBe(['visualization_key', 'stages', 'floating_filters'])
        ->and($schema['visualization_key'])->toBe('funnels.sales')
        ->and($schema['stages']->first()['field'])->toBe('value_visitors');
});

it('reports the kind from getVisualizationType on the query event', function () {
    Event::fake();

    (new SalesFunnel)->handleData(new ChartDataRequest);

    Event::assertDispatched(
        VisualizationQueryExecuted::class,
        fn ($event) => $event->visualizationType === 'funnel' && $event->rowCount === 1
    );
});

it('reports an overridden kind on the query event of a built-in base', function () {
    Event::fake();

    (new FunnelDataGrid)->handleData(new DataGridDataRequest);

    Event::assertDispatched(
        VisualizationQueryExecuted::class,
        fn ($event) => $event->visualizationType === 'funnel'
    );
});
