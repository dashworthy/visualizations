<?php

use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;

test('authorize returns true', function () {
    $request = new DataGridDataRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules contain expected keys', function () {
    $request = new DataGridDataRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['per_page', 'page', 'first', 'last', 'sorts', 'sorts.*', 'filter_sets', 'filter_sets.*']);
});

test('valid data passes validation', function () {
    $request = DataGridDataRequest::create('/grid-data', 'POST', [
        'per_page' => 50,
        'page' => 1,
    ]);
    $request->setContainer(app());

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('per page must be positive integer', function () {
    $request = DataGridDataRequest::create('/grid-data', 'POST', ['per_page' => 0]);
    $request->setContainer(app());

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('per_page');
});

test('per page max 1000', function () {
    $request = DataGridDataRequest::create('/grid-data', 'POST', ['per_page' => 1001]);
    $request->setContainer(app());

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('per_page');
});

test('page must be positive integer', function () {
    $request = DataGridDataRequest::create('/grid-data', 'POST', ['page' => 0]);
    $request->setContainer(app());

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('page');
});

test('first must be non negative', function () {
    $request = DataGridDataRequest::create('/grid-data', 'POST', ['first' => -1]);
    $request->setContainer(app());

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('first');
});

test('last must be non negative', function () {
    $request = DataGridDataRequest::create('/grid-data', 'POST', ['last' => -1]);
    $request->setContainer(app());

    $validator = validator($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())->toHaveKey('last');
});
