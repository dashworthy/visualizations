<?php

use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGridWithAuthorization;

test('get route prefix returns grids', function () {
    $grid = new UserDataGrid;

    expect($grid->getRoutePrefix())->toBe('grids');
});

test('get route name generates correct name', function () {
    $grid = new UserDataGrid;

    expect($grid->getRouteName())->toBe('grids.users');
});

test('get route path generates correct path', function () {
    $grid = new UserDataGrid;

    expect($grid->getRoutePath())->toBe('grids/users');
});

test('get data grid key delegates to route name', function () {
    $grid = new UserDataGrid;

    expect($grid->getVisualizationKey())->toBe($grid->getRouteName());
});

test('get route name for authorization grid', function () {
    $grid = new UserDataGridWithAuthorization;

    expect($grid->getRouteName())->toBe('grids.users');
});

test('get default sorts returns empty by default', function () {
    $grid = new UserDataGridWithAuthorization;

    expect($grid->getDefaultSorts())->toHaveCount(0);
});

test('get floating filters returns empty by default', function () {
    $grid = new UserDataGridWithAuthorization;

    expect($grid->getFloatingFilters())->toHaveCount(0);
});

test('get bulk actions returns empty by default', function () {
    $grid = new UserDataGridWithAuthorization;

    expect($grid->getBulkActions())->toHaveCount(0);
});

test('get inline actions returns empty by default', function () {
    $grid = new UserDataGridWithAuthorization;

    expect($grid->getInlineActions())->toHaveCount(0);
});
