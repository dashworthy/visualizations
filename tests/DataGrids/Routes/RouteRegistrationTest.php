<?php

use Illuminate\Support\Facades\Route;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;

test('route macro registers all routes', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.data'))->not->toBeNull();
    expect($routes->getByName('grids.users.schema'))->not->toBeNull();
    expect($routes->getByName('grids.users.actions.inline'))->not->toBeNull();
    expect($routes->getByName('grids.users.actions.bulk'))->not->toBeNull();
});

test('route macro registers correct methods', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect(in_array('POST', $routes->getByName('grids.users.data')->methods()))->toBeTrue();
    expect(in_array('POST', $routes->getByName('grids.users.schema')->methods()))->toBeTrue();
    expect(in_array('POST', $routes->getByName('grids.users.actions.inline')->methods()))->toBeTrue();
    expect(in_array('POST', $routes->getByName('grids.users.actions.bulk')->methods()))->toBeTrue();
});

test('route macro registers correct uris', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.data')->uri())->toBe('grids/users/data');
    expect($routes->getByName('grids.users.schema')->uri())->toBe('grids/users/schema');
    expect($routes->getByName('grids.users.actions.inline')->uri())->toBe('grids/users/actions/inline');
    expect($routes->getByName('grids.users.actions.bulk')->uri())->toBe('grids/users/actions/bulk');
});

test('route macro throws for non existent class', function () {
    expect(fn () => Route::dataGrid('App\\NonExistent\\FakeGrid'))
        ->toThrow(Exception::class, 'Could not find class matching');
});

test('route macro throws for non datagrid class', function () {
    expect(fn () => Route::dataGrid(stdClass::class))
        ->toThrow(Exception::class, 'is not a valid DataGrid');
});

test('route macro does not register export routes when handleExport absent', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.export'))->toBeNull();
    expect($routes->getByName('grids.users.export.status'))->toBeNull();
    expect($routes->getByName('grids.users.export.download'))->toBeNull();
});

test('route macro does not register views index route when handleViews absent', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.views'))->toBeNull();
});

test('route macro does not register views store route when handleViewStore absent', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.views.store'))->toBeNull();
});

test('route macro does not register views destroy route when handleViewDestroy absent', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.views.destroy'))->toBeNull();
});
