<?php

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Datasets\Bar;
use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\Charts\Labels\NullLabel;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Tests\Fixtures\Charts\NullLabelChart;
use Dashworthy\Visualizations\Tests\Fixtures\Charts\RevenueChart;
use Dashworthy\Visualizations\Tests\Fixtures\Charts\RevenueWithFloatingFiltersChart;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

it('generates the route name from the class name', function () {
    $chart = new RevenueChart;

    expect($chart->getRouteName())->toBe('charts.revenues');
});

it('generates the route path from the class name', function () {
    $chart = new RevenueChart;

    expect($chart->getRoutePath())->toBe('charts/revenues');
});

it('uses the route name as the chart key', function () {
    $chart = new RevenueChart;

    expect($chart->getVisualizationKey())->toBe($chart->getRouteName());
});

it('defaults the route prefix to charts', function () {
    $chart = new RevenueChart;

    expect($chart->getRoutePrefix())->toBe('charts');
});

it('builds a schema with chart key, label, datasets, and floating filters', function () {
    $schema = RevenueChart::schema();

    expect($schema)->toHaveKeys(['visualization_key', 'label', 'datasets', 'floating_filters']);

    expect($schema['label'])->toBe([
        'field' => 'label_date',
        'header' => 'date',
        'meta' => [],
    ]);

    expect($schema['datasets'])->toHaveCount(2);

    expect($schema['datasets']->first())->toBe([
        'field' => 'dataset_revenue',
        'header' => 'Revenue',
        'type' => 'bar',
        'meta' => [],
    ]);
});

it('builds a schema with an empty label when using NullLabel', function () {
    $schema = NullLabelChart::schema();

    expect($schema['label'])->toBe([]);
});

it('returns an empty collection for floating filters by default', function () {
    $chart = new RevenueChart;

    expect($chart->getFloatingFilters())->toBeEmpty();
});

it('includes floating filters in the schema', function () {
    $schema = RevenueWithFloatingFiltersChart::schema();

    expect($schema['floating_filters'])->toHaveCount(1);

    expect($schema['floating_filters']->first())->toBe([
        'field' => 'floating_filter_date_range',
        'header' => 'Date Range',
        'type' => 'date_range',
        'meta' => [],
    ]);
});

it('includes an empty floating_filters collection in the schema when none are defined', function () {
    $schema = RevenueChart::schema();

    expect($schema['floating_filters'])->toBeEmpty();
});

// getVisualizables() / concat() correctness tests
// These prove that no items are lost when combining label, datasets, and floating filters.
// merge() can silently drop items when a collection contains string keys that collide with
// those already present; concat() always appends regardless of key type.

it('getVisualizables returns label + datasets in order', function () {
    $chart = new RevenueChart;
    $visualizables = $chart->getVisualizables();

    // RevenueChart has 1 label + 2 datasets = 3 total
    expect($visualizables)->toHaveCount(3);
    expect($visualizables->get(0))->toBeInstanceOf(Label::class);
    expect($visualizables->get(1))->toBeInstanceOf(Dataset::class);
    expect($visualizables->get(2))->toBeInstanceOf(Dataset::class);
});

it('getVisualizables appends floating filters after datasets', function () {
    $chart = new RevenueWithFloatingFiltersChart;
    $visualizables = $chart->getVisualizables();

    // RevenueWithFloatingFiltersChart has 1 label + 1 dataset + 1 floating filter = 3 total
    expect($visualizables)->toHaveCount(3);
    expect($visualizables->get(0))->toBeInstanceOf(Label::class);
    expect($visualizables->get(1))->toBeInstanceOf(Dataset::class);
    expect($visualizables->get(2))->toBeInstanceOf(FloatingFilter::class);
});

it('getVisualizables excludes the NullLabel and preserves datasets and floating filters', function () {
    $chart = new NullLabelChart;
    $visualizables = $chart->getVisualizables();

    // NullLabelChart has 1 dataset and no floating filters — NullLabel must not appear
    expect($visualizables)->toHaveCount(1);
    expect($visualizables->get(0))->toBeInstanceOf(Dataset::class);
    expect($visualizables->contains(fn ($v) => $v instanceof NullLabel))->toBeFalse();
});

it('fires VisualizationQueryExecuted when handleData is called', function () {
    Event::fake();

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
        $table->decimal('refunds', 10, 2)->default(0);
        $table->string('order_date');
    });

    DB::table('orders')->insert([
        ['total' => 100.00, 'refunds' => 5.00, 'order_date' => '2026-01-01'],
        ['total' => 200.00, 'refunds' => 0.00, 'order_date' => '2026-01-02'],
    ]);

    $chart = new RevenueChart;
    $request = ChartDataRequest::create('/charts/revenues/data', 'POST', [
        'filter_sets' => [],
    ]);

    $chart->handleData($request);

    Event::assertDispatched(
        VisualizationQueryExecuted::class,
        fn ($event) => $event->visualizationKey === 'charts.revenues'
            && $event->visualizationType === 'chart'
            && $event->rowCount === 2
            && $event->durationMs > 0
            && str_contains($event->sql, 'orders')
    );

    Schema::dropIfExists('orders');
});

it('getVisualizables does not lose items when datasets collection keys start at zero', function () {
    // Demonstrates the concat() vs merge() distinction: merge() on two numeric-keyed
    // collections re-indexes, but if the second collection were string-keyed, merge()
    // would silently overwrite. concat() always appends safely.
    $chart = new RevenueChart;

    // Simulate the risky case: a string-keyed datasets collection
    $stringKeyedChart = new class extends RevenueChart
    {
        public function getDatasets(): Collection
        {
            return collect([
                'first' => Bar::make('SUM(total)', 'revenue')->header('Revenue'),
                'second' => Bar::make('SUM(refunds)', 'refunds')->header('Refunds'),
            ]);
        }
    };

    $visualizables = $stringKeyedChart->getVisualizables();

    // All three items (label + 2 datasets) must be present
    expect($visualizables)->toHaveCount(3);
    expect($visualizables->first())->toBeInstanceOf(Label::class);
});
