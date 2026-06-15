<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    $path = app_path('Charts');
    if (File::isDirectory($path)) {
        File::deleteDirectory($path);
    }
});

it('creates a chart class in the Charts directory', function () {
    $this->artisan('make:chart', ['name' => 'RevenueChart'])
        ->assertExitCode(0);

    expect(file_exists(app_path('Charts/RevenueChart.php')))->toBeTrue();
});

it('generates a class that extends Chart', function () {
    $this->artisan('make:chart', ['name' => 'RevenueChart'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Charts/RevenueChart.php'));

    expect($contents)
        ->toContain('class RevenueChart extends Chart')
        ->toContain('use Dashworthy\Visualizations\Charts\Abstracts\Chart;');
});

it('generates a class with getLabel, getDatasets, and getQuery methods', function () {
    $this->artisan('make:chart', ['name' => 'RevenueChart'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Charts/RevenueChart.php'));

    expect($contents)
        ->toContain('public function getLabel(): Label')
        ->toContain('public function getDatasets(): Collection')
        ->toContain('public function getQuery(): Builder');
});

it('places the class in the App\Charts namespace', function () {
    $this->artisan('make:chart', ['name' => 'RevenueChart'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Charts/RevenueChart.php'));

    expect($contents)->toContain('namespace App\Charts;');
});

it('replaces the CHART_NAME placeholder with the provided name', function () {
    $this->artisan('make:chart', ['name' => 'SalesChart'])
        ->assertExitCode(0);

    $contents = file_get_contents(app_path('Charts/SalesChart.php'));

    expect($contents)
        ->toContain('class SalesChart extends Chart')
        ->not->toContain('CHART_NAME');
});
