<?php

use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridBulkActionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

test('constructor sets name and closure', function () {
    $closure = function (): void {};
    $action = new Action('test', $closure);

    expect($action->name)->toBe('test');
    expect($action->closure)->toBe($closure);
});

test('make sets name and closure', function () {
    $closure = function (): void {};
    $action = Action::make('test', $closure);

    expect($action->name)->toBe('test');
    expect($action->closure)->toBe($closure);
});

test('is authorized', function () {
    $closure = function (): void {};
    $action = new Action('test', $closure);
    $request = DataGridBulkActionRequest::create('/test', 'POST');

    expect($action->isAuthorized($request))->toBeTrue();

    Gate::shouldReceive('allows')->with('view')->andReturn(true);
    $action->withAuthorization('view');
    expect($action->isAuthorized($request))->toBeTrue();

    Gate::shouldReceive('allows')->with(['view', 'edit'])->andReturn(true);
    $action->withAuthorization(['view', 'edit']);
    expect($action->isAuthorized($request))->toBeTrue();

    $action->withAuthorization(fn ($req): true => true);
    expect($action->isAuthorized($request))->toBeTrue();
});

test('handle with empty rows returns empty array', function () {
    $closure = function (): void {};
    $action = new Action('test', $closure);
    $dataGrid = Mockery::mock(DataGrid::class);
    $rows = new Collection;

    expect($action->handle($dataGrid, $rows))->toEqual([]);
});

test('handle with simple rows maps each', function () {
    $closure = fn ($id) => $id;
    $action = new Action('test', $closure);
    $dataGrid = Mockery::mock(DataGrid::class);
    $rows = new Collection([1, 2, 3]);

    expect($action->handle($dataGrid, $rows))->toEqual([1, 2, 3]);
});

test('handle with single row returns array', function () {
    $closure = fn ($id) => $id;
    $action = new Action('test', $closure);
    $dataGrid = Mockery::mock(DataGrid::class);
    $rows = new Collection([42]);

    expect($action->handle($dataGrid, $rows))->toEqual([42]);
});

test('handle with single row returning redirect response returns response', function () {
    $redirect = redirect('/dashboard');
    $closure = fn ($id): RedirectResponse|\Illuminate\Routing\Redirector => $redirect;
    $action = new Action('test', $closure);
    $dataGrid = Mockery::mock(DataGrid::class);
    $rows = new Collection([1]);

    $result = $action->handle($dataGrid, $rows);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    expect($result)->toBe($redirect);
});
