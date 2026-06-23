<?php

use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridBulkActionRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridInlineActionRequest;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGridWithActionRules;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;

/**
 * Builds a request whose resolved route controller is the given grid.
 */
function actionRequest(string $class, string $uri, array $payload)
{
    $grid = new UserDataGridWithActionRules;
    $route = new Route('POST', $uri, []);
    $route->controller = $grid;
    // Laravel 13: Route::getController() only returns a preset controller when isControllerAction() is true, which requires action['uses'] to be a non-Closure string.
    $route->action['uses'] = UserDataGridWithActionRules::class;

    $request = $class::create($uri, 'POST', $payload);
    $request->setRouteResolver(fn () => $route);
    $request->setContainer(app());

    return $request;
}

test('inline request rejects a non-existent row key for a rules-backed action', function () {
    $request = actionRequest(DataGridInlineActionRequest::class, '/inline', [
        'action' => 'Edit',
        'row_key' => 999,
    ]);

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('row_key');
});

test('inline request accepts an existing row key for a rules-backed action', function () {
    DB::table('users')->insert([
        'name' => 'John', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $request = actionRequest(DataGridInlineActionRequest::class, '/inline', [
        'action' => 'Edit',
        'row_key' => 1,
    ]);

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('inline request adds no existence rule for an action without rules', function () {
    $request = actionRequest(DataGridInlineActionRequest::class, '/inline', [
        'action' => 'NoRules',
        'row_key' => 999,
    ]);

    $validator = validator($request->all(), $request->rules());

    // Only `required` applies, so a present-but-nonexistent key passes.
    expect($validator->fails())->toBeFalse();
});

test('bulk request accepts an existing row key for a rules-backed action', function () {
    DB::table('users')->insert([
        'name' => 'John', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $request = actionRequest(DataGridBulkActionRequest::class, '/bulk', [
        'action' => 'Delete',
        'row_keys' => [1],
    ]);

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('bulk request rejects a non-existent row key for a rules-backed action', function () {
    $request = actionRequest(DataGridBulkActionRequest::class, '/bulk', [
        'action' => 'Delete',
        'row_keys' => [999],
    ]);

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('row_keys.0');
});
