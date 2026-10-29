<?php

use Dashworthy\Visualizations\Rules\ScalarRule;
use Illuminate\Support\Facades\Validator;

it('passes a single value', function (mixed $value) {
    $validator = Validator::make(['value' => $value], ['value' => [new ScalarRule]]);

    expect($validator->passes())->toBeTrue();
})->with([
    'a string' => 'Ada',
    'an integer' => 7,
    'a float' => 1.5,
    'a boolean' => false,
]);

it('fails a list or a map', function (array $value) {
    $validator = Validator::make(['value' => $value], ['value' => [new ScalarRule]]);

    expect($validator->errors()->first('value'))->toBe('The value must be a single value.');
})->with([
    'an empty list' => [[]],
    'a list' => [['Ada', 'Bob']],
    'a map' => [['name' => 'Ada']],
]);

it('admits null alongside nullable', function () {
    $validator = Validator::make(['value' => null], ['value' => ['nullable', new ScalarRule]]);

    expect($validator->passes())->toBeTrue();
});
