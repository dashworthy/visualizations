<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    $path = app_path('Metrics');
    if (File::isDirectory($path)) {
        File::deleteDirectory($path);
    }
});

it('creates a metric class in the Metrics directory', function () {
    $this->artisan('make:metric', ['name' => 'RevenueMetric'])
        ->assertExitCode(0);

    expect(file_exists(app_path('Metrics/RevenueMetric.php')))->toBeTrue();
});

it('generates a class that extends Metric', function () {
    $this->artisan('make:metric', ['name' => 'RevenueMetric'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Metrics/RevenueMetric.php'));

    expect($contents)
        ->toContain('class RevenueMetric extends Metric')
        ->toContain('use Dashworthy\Visualizations\Metrics\Abstracts\Metric;');
});

it('generates a class with getValue and getQuery methods', function () {
    $this->artisan('make:metric', ['name' => 'RevenueMetric'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Metrics/RevenueMetric.php'));

    expect($contents)
        ->toContain('public function getValue(): Value')
        ->toContain('public function getQuery(): Builder');
});

it('places the class in the App\Metrics namespace', function () {
    $this->artisan('make:metric', ['name' => 'RevenueMetric'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Metrics/RevenueMetric.php'));

    expect($contents)->toContain('namespace App\Metrics;');
});

it('replaces the METRIC_NAME placeholder with the provided name', function () {
    $this->artisan('make:metric', ['name' => 'SalesMetric'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Metrics/SalesMetric.php'));

    expect($contents)
        ->toContain('class SalesMetric extends Metric')
        ->not->toContain('METRIC_NAME');
});
