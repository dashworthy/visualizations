<?php

use Dashworthy\Visualizations\Tests\Fixtures\Metrics\RevenueMetric;
use Illuminate\Support\Facades\Route;

test('route macro registers all routes', function () {
    Route::metric(RevenueMetric::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('metrics.revenues.data'))->not->toBeNull();
    expect($routes->getByName('metrics.revenues.schema'))->not->toBeNull();
});

test('route macro registers correct methods', function () {
    Route::metric(RevenueMetric::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect(in_array('POST', $routes->getByName('metrics.revenues.data')->methods()))->toBeTrue();
    expect(in_array('POST', $routes->getByName('metrics.revenues.schema')->methods()))->toBeTrue();
});

test('route macro registers correct uris', function () {
    Route::metric(RevenueMetric::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('metrics.revenues.data')->uri())->toBe('metrics/revenues/data');
    expect($routes->getByName('metrics.revenues.schema')->uri())->toBe('metrics/revenues/schema');
});

test('route macro throws for non existent class', function () {
    expect(fn () => Route::metric('App\\NonExistent\\FakeMetric'))
        ->toThrow(Exception::class, 'Could not find class matching');
});

test('route macro throws for non metric class', function () {
    expect(fn () => Route::metric(stdClass::class))
        ->toThrow(Exception::class, 'is not a valid Metric');
});
