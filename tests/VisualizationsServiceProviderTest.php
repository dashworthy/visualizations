<?php

use Dashworthy\Visualizations\FilterOperations\Equality\Equals;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

it('registers the generator commands', function (string $command) {
    expect(Artisan::all())->toHaveKey($command);
})->with(['make:chart', 'make:datagrid', 'make:hydrator', 'make:metric']);

it('merges the package config', function () {
    expect(config('visualizations.filters'))->toContain(Equals::class);
});

it('registers a route macro for each kind of visualization', function (string $macro) {
    expect(Route::hasMacro($macro))->toBeTrue();
})->with(['chart', 'dataGrid', 'metric']);
