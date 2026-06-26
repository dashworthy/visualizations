<?php

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Metrics\Http\Requests\MetricDataRequest;
use Dashworthy\Visualizations\Metrics\Http\Requests\MetricSchemaRequest;
use Dashworthy\Visualizations\Metrics\Value;
use Dashworthy\Visualizations\Tests\Fixtures\Metrics\CachedRevenueMetric;
use Dashworthy\Visualizations\Tests\Fixtures\Metrics\RevenueMetric;
use Dashworthy\Visualizations\Tests\Fixtures\Metrics\RevenueWithFloatingFiltersMetric;
use Dashworthy\Visualizations\Tests\Fixtures\Metrics\RevenueWithTotalFloatingFilterMetric;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

it('generates the route name from the class name', function () {
    $metric = new RevenueMetric;

    expect($metric->getRouteName())->toBe('metrics.revenues');
});

it('generates the route path from the class name', function () {
    $metric = new RevenueMetric;

    expect($metric->getRoutePath())->toBe('metrics/revenues');
});

it('uses the route name as the visualization key', function () {
    $metric = new RevenueMetric;

    expect($metric->getVisualizationKey())->toBe($metric->getRouteName());
});

it('defaults the route prefix to metrics', function () {
    $metric = new RevenueMetric;

    expect($metric->getRoutePrefix())->toBe('metrics');
});

it('returns an empty collection for floating filters by default', function () {
    $metric = new RevenueMetric;

    expect($metric->getFloatingFilters())->toBeEmpty();
});

it('builds a schema with visualization_key, value, and floating_filters', function () {
    $schema = RevenueMetric::schema();

    expect($schema)->toHaveKeys(['visualization_key', 'value', 'floating_filters']);
    expect($schema['visualization_key'])->toBe('metrics.revenues');
    expect($schema['value'])->toBe([
        'field' => 'value_revenue',
        'header' => 'Total Revenue',
        'meta' => [],
    ]);
    expect($schema['floating_filters'])->toBeEmpty();
});

it('includes floating filters in the schema', function () {
    $schema = RevenueWithFloatingFiltersMetric::schema();

    expect($schema['floating_filters'])->toHaveCount(1);
    expect($schema['floating_filters']->first())->toBe([
        'field' => 'floating_filter_date_range',
        'header' => 'Date Range',
        'type' => 'date_range',
        'meta' => [],
    ]);
});

it('getVisualizables returns value followed by floating filters', function () {
    $metric = new RevenueWithFloatingFiltersMetric;
    $visualizables = $metric->getVisualizables();

    expect($visualizables)->toHaveCount(2);
    expect($visualizables->get(0))->toBeInstanceOf(Value::class);
    expect($visualizables->get(1))->toBeInstanceOf(FloatingFilter::class);
});

it('handleSchema returns the schema as JSON', function () {
    $metric = new RevenueMetric;
    $request = new MetricSchemaRequest;

    $response = $metric->handleSchema($request);
    $data = json_decode($response->getContent(), true);

    expect($data)->toHaveKeys(['visualization_key', 'value', 'floating_filters']);
    expect($data['visualization_key'])->toBe('metrics.revenues');
    expect($data['value']['field'])->toBe('value_revenue');
    expect($data['value']['header'])->toBe('Total Revenue');
});

it('handleData returns the aggregate value', function () {
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
    });

    DB::table('orders')->insert([
        ['total' => 100.00],
        ['total' => 200.00],
        ['total' => 50.00],
    ]);

    $metric = new RevenueMetric;
    $request = new MetricDataRequest;

    $response = $metric->handleData($request);
    $data = json_decode($response->getContent(), true);

    expect($data)->toHaveKey('value');
    expect((float) $data['value'])->toBe(350.0);

    Schema::dropIfExists('orders');
});

it('handleData returns null value when query returns no rows', function () {
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
    });

    $metric = new RevenueMetric;
    $request = new MetricDataRequest;

    $response = $metric->handleData($request);
    $data = json_decode($response->getContent(), true);

    expect($data['value'])->toBeNull();

    Schema::dropIfExists('orders');
});

it('handleData applies filter_sets to narrow the aggregate', function () {
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
    });

    DB::table('orders')->insert([
        ['total' => 100.00],
        ['total' => 200.00],
        ['total' => 50.00],
    ]);

    $metric = new RevenueWithTotalFloatingFilterMetric;
    $request = MetricDataRequest::create('/metrics/revenues/data', 'POST', [
        'filter_sets' => [
            [
                'filter_set_operator' => 'and',
                'filters' => [
                    ['field' => 'floating_filter_total', 'value' => 100, 'filter_operator' => 'gte'],
                ],
            ],
        ],
    ]);

    $response = $metric->handleData($request);
    $data = json_decode($response->getContent(), true);

    expect((float) $data['value'])->toBe(300.0);

    Schema::dropIfExists('orders');
});

it('fires VisualizationQueryExecuted when handleData is called', function () {
    Event::fake();

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
    });

    DB::table('orders')->insert([
        ['total' => 100.00],
        ['total' => 200.00],
    ]);

    $metric = new RevenueMetric;
    $request = new MetricDataRequest;

    $metric->handleData($request);

    Event::assertDispatched(
        VisualizationQueryExecuted::class,
        fn ($event) => $event->visualizationKey === 'metrics.revenues'
            && $event->visualizationType === 'metric'
            && $event->rowCount === 1
            && $event->durationMs > 0
    );

    Schema::dropIfExists('orders');
});

it('caching metric serves the second request from cache', function () {
    config()->set('cache.default', 'array');
    config()->set('visualizations.cache.enabled', true);

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
    });

    DB::table('orders')->insert([
        ['total' => 100.00],
        ['total' => 200.00],
    ]);

    $metric = new CachedRevenueMetric;
    $request = MetricDataRequest::create('/metrics/revenues/data', 'POST', ['filter_sets' => []]);

    $first = json_decode($metric->handleData($request)->getContent(), true);
    expect((float) $first['value'])->toBe(300.0);

    DB::table('orders')->insert([['total' => 1000.00]]);

    $second = json_decode($metric->handleData($request)->getContent(), true);
    expect((float) $second['value'])->toBe(300.0);

    Schema::dropIfExists('orders');
});

it('caching metric fires fromCache event on a hit', function () {
    config()->set('cache.default', 'array');
    config()->set('visualizations.cache.enabled', true);

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
    });

    DB::table('orders')->insert([['total' => 100.00]]);

    $metric = new CachedRevenueMetric;
    $request = MetricDataRequest::create('/metrics/revenues/data', 'POST', ['filter_sets' => []]);

    $metric->handleData($request);

    Event::fake();
    $metric->handleData($request);

    Event::assertDispatched(
        VisualizationQueryExecuted::class,
        fn ($event) => $event->fromCache === true
            && $event->sql === null
            && $event->visualizationType === 'metric'
    );

    Schema::dropIfExists('orders');
});

it('caching metric stdClass row survives serializing file store round-trip', function () {
    config()->set('cache.default', 'file');
    config()->set('visualizations.cache.enabled', true);
    Cache::flush();

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->decimal('total', 10, 2);
    });

    DB::table('orders')->insert([
        ['total' => 100.00],
        ['total' => 200.00],
    ]);

    $metric = new CachedRevenueMetric;
    $request = MetricDataRequest::create('/metrics/revenues/data', 'POST', ['filter_sets' => []]);

    // Prime the cache (miss — writes stdClass row to the file store, serializing it).
    $first = json_decode($metric->handleData($request)->getContent(), true);
    expect((float) $first['value'])->toBe(300.0);

    // Insert a new row — must NOT change the cached value.
    DB::table('orders')->insert([['total' => 1000.00]]);

    // Second call deserializes from the file store.
    $second = json_decode($metric->handleData($request)->getContent(), true);
    expect($second)->toBe($first);
    expect((float) $second['value'])->toBe(300.0);

    Schema::dropIfExists('orders');
});
