<?php

use Dashworthy\Visualizations\Contracts\HandlesDataGridViews;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\LegacyViewUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\SavedViewUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('route macro registers all routes', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.data'))->not->toBeNull();
    expect($routes->getByName('grids.users.schema'))->not->toBeNull();
});

test('route macro registers correct methods', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect(in_array('POST', $routes->getByName('grids.users.data')->methods()))->toBeTrue();
    expect(in_array('POST', $routes->getByName('grids.users.schema')->methods()))->toBeTrue();
});

test('route macro registers correct uris', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.data')->uri())->toBe('grids/users/data');
    expect($routes->getByName('grids.users.schema')->uri())->toBe('grids/users/schema');
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

test('route macro does not register views update route when handleViewUpdate absent', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.views.update'))->toBeNull();
});

test('route macro does not register views default route when handleViewDefault absent', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.views.default'))->toBeNull();
});

test('route macro does not register views clear default route when handleViewClearDefault absent', function () {
    Route::dataGrid(UserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName('grids.users.views.clear-default'))->toBeNull();
});

test('route macro registers every views route for a grid implementing HandlesDataGridViews', function (string $name, string $method, string $uri, string $handler) {
    Route::dataGrid(SavedViewUserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    $route = $routes->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain($method)
        ->and($route->uri())->toBe($uri)
        ->and($route->getActionName())->toBe(SavedViewUserDataGrid::class.'@'.$handler);
})->with([
    'index' => ['grids.saved-view-users.views', 'GET', 'grids/saved-view-users/views', 'handleViews'],
    'store' => ['grids.saved-view-users.views.store', 'POST', 'grids/saved-view-users/views', 'handleViewStore'],
    'update' => ['grids.saved-view-users.views.update', 'PATCH', 'grids/saved-view-users/views/{view}', 'handleViewUpdate'],
    'default' => ['grids.saved-view-users.views.default', 'PUT', 'grids/saved-view-users/views/{view}/default', 'handleViewDefault'],
    'clear default' => ['grids.saved-view-users.views.clear-default', 'DELETE', 'grids/saved-view-users/views/{view}/default', 'handleViewClearDefault'],
    'destroy' => ['grids.saved-view-users.views.destroy', 'DELETE', 'grids/saved-view-users/views/{view}', 'handleViewDestroy'],
]);

test('route macro still registers the deprecated method-detected views routes for a grid not implementing HandlesDataGridViews', function (string $name, string $method, string $uri, string $handler) {
    Route::dataGrid(LegacyViewUserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    $route = $routes->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain($method)
        ->and($route->uri())->toBe($uri)
        ->and($route->getActionName())->toBe(LegacyViewUserDataGrid::class.'@'.$handler);
})->with([
    'index' => ['grids.legacy-view-users.views', 'GET', 'grids/legacy-view-users/views', 'handleViews'],
    'store' => ['grids.legacy-view-users.views.store', 'POST', 'grids/legacy-view-users/views', 'handleViewStore'],
    'destroy' => ['grids.legacy-view-users.views.destroy', 'DELETE', 'grids/legacy-view-users/views/{view}', 'handleViewDestroy'],
]);

test('route macro never registers the update, default and clear default views routes for a grid not implementing HandlesDataGridViews', function (string $name) {
    Route::dataGrid(LegacyViewUserDataGrid::class);

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($routes->getByName($name))->toBeNull();
})->with([
    'update' => ['grids.legacy-view-users.views.update'],
    'default' => ['grids.legacy-view-users.views.default'],
    'clear default' => ['grids.legacy-view-users.views.clear-default'],
]);

test('HandlesDataGridViews handlers take only the request, so a prefixed group parameter cannot shift into the view id', function () {
    $methods = (new ReflectionClass(HandlesDataGridViews::class))->getMethods();

    expect($methods)->toHaveCount(6);

    foreach ($methods as $method) {
        $parameters = $method->getParameters();

        expect($parameters)->toHaveCount(1, $method->getName().' must take only the request')
            ->and((string) $parameters[0]->getType())->toBe(Request::class);
    }
});

test('a grid implementing HandlesDataGridViews under a prefixed group reads the view and group parameters by name', function (string $method, string $uri, string $handler) {
    Route::prefix('t/{tenant}')->group(fn () => Route::dataGrid(SavedViewUserDataGrid::class));

    $this->json($method, $uri)
        ->assertOk()
        ->assertExactJson(['handler' => $handler, 'tenant' => 'acme', 'view' => '42']);
})->with([
    'update' => ['PATCH', 't/acme/grids/saved-view-users/views/42', 'handleViewUpdate'],
    'default' => ['PUT', 't/acme/grids/saved-view-users/views/42/default', 'handleViewDefault'],
    'clear default' => ['DELETE', 't/acme/grids/saved-view-users/views/42/default', 'handleViewClearDefault'],
    'destroy' => ['DELETE', 't/acme/grids/saved-view-users/views/42', 'handleViewDestroy'],
]);
