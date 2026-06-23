<?php

use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DuplicateSlugDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\OverrideSlugDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Illuminate\Support\Facades\Route;

test('route macro registers data and schema routes', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.data'))->not->toBeNull();
    expect($routes->getByName('grids.users.schema'))->not->toBeNull();
});

test('route macro registers a dedicated route per inline and bulk action', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    $inline = $routes->getByName('grids.users.actions.inline.edit');
    $bulk = $routes->getByName('grids.users.actions.bulk.create');

    expect($inline)->not->toBeNull();
    expect($bulk)->not->toBeNull();
    expect($inline->uri())->toBe('grids/users/actions/inline/edit');
    expect($bulk->uri())->toBe('grids/users/actions/bulk/create');
    expect(in_array('POST', $inline->methods()))->toBeTrue();
    expect(in_array('POST', $bulk->methods()))->toBeTrue();
    expect($inline->defaults['action'])->toBe('edit');
    expect($bulk->defaults['action'])->toBe('create');
});

test('route macro no longer registers the shared action routes', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.actions.inline'))->toBeNull();
    expect($routes->getByName('grids.users.actions.bulk'))->toBeNull();
});

test('route macro throws on duplicate action slugs within a collection', function () {
    expect(fn () => Route::dataGrid(DuplicateSlugDataGrid::class))
        ->toThrow(InvalidArgumentException::class);
});

test('route macro uses multi-word auto-slugs and explicit overrides', function () {
    $grid = new OverrideSlugDataGrid;
    Route::dataGrid($grid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    $base = $grid->getRouteName();

    // Multi-word name auto-slugs to disable-user.
    $multi = $routes->getByName($base.'.actions.inline.disable-user');
    expect($multi)->not->toBeNull();
    expect($multi->uri())->toBe(ltrim($grid->getRoutePath(), '/').'/actions/inline/disable-user');

    // Explicit override wins over the name-derived slug.
    expect($routes->getByName($base.'.actions.inline.arc'))->not->toBeNull();
    expect($routes->getByName($base.'.actions.inline.archive'))->toBeNull();
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
