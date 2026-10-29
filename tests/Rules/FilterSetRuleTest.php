<?php

use Dashworthy\Visualizations\FilterOperations\Equality\Equals;
use Dashworthy\Visualizations\Rules\FilterSetRule;
use Dashworthy\Visualizations\Tests\Fixtures\FilterOperations\WholeNumberEquals;
use Illuminate\Support\Facades\Validator;

it('validates filter set structure', function () {
    $data = [
        'filter_sets.*' => [
            [
                'filter_set_operator' => 'AND',
                'filters' => [
                    [
                        'field' => 'name',
                        'value' => 'test',
                        'filter_operator' => 'EQUALS',
                    ],
                ],
            ],
        ],
    ];

    $validator = Validator::make($data, [
        'filter_sets' => ['array'],
        'filter_sets.*' => ['required', new FilterSetRule],
    ]);

    expect($validator->passes())->toBeTrue();
});

it('fails with missing fields', function () {
    $data = [
        'filter_sets' => [
            [
                // missing filter_set_operator
                'filters' => [
                    [
                        // missing field, value, filter_operator
                    ],
                ],
            ],
        ],
    ];

    $validator = Validator::make($data, [
        'filter_sets.*' => [new FilterSetRule],
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->toArray())
        ->toHaveKey('filter_sets.0.filter_set_operator')
        ->toHaveKey('filter_sets.0.filters.0.field')
        ->toHaveKey('filter_sets.0.filters.0.value')
        ->toHaveKey('filter_sets.0.filters.0.filter_operator');

});

/**
 * @param  array<string, mixed>  $filter
 */
function validateFilterSetWith(array $filter): Illuminate\Validation\Validator
{
    return Validator::make(
        ['filter_sets' => [['filter_set_operator' => 'and', 'filters' => [$filter]]]],
        ['filter_sets.*' => [new FilterSetRule]],
    );
}

it('rejects a list for an operator that binds one value', function (array $value) {
    // whereRaw() binds a list as several values, shifting every later binding into the wrong placeholder.
    $validator = validateFilterSetWith(['field' => 'column_ID', 'value' => $value, 'filter_operator' => 'equals']);

    expect($validator->errors()->keys())->toBe(['filter_sets.0.filters.0.value']);
})->with([
    'an empty list' => [[]],
    'a list' => [['Ada', 'Bob']],
    'a map' => [['name' => 'Ada']],
]);

it('accepts a single value or null for an operator that binds one value', function (mixed $value) {
    $validator = validateFilterSetWith(['field' => 'column_ID', 'value' => $value, 'filter_operator' => 'gt']);

    expect($validator->passes())->toBeTrue();
})->with([
    'a relative date' => '-7 days',
    'a number' => 7,
    'null' => [null],
]);

it('accepts a list of single values for in and notIn', function (string $operator) {
    $validator = validateFilterSetWith(['field' => 'column_Status', 'value' => ['open', null], 'filter_operator' => $operator]);

    expect($validator->passes())->toBeTrue();
})->with(['in', 'notIn']);

it('rejects a list nested inside an in list', function (string $operator) {
    $validator = validateFilterSetWith(['field' => 'column_Status', 'value' => ['open', ['a', 'b']], 'filter_operator' => $operator]);

    expect($validator->errors()->keys())->toBe(['filter_sets.0.filters.0.value.1']);
})->with(['in', 'notIn']);

it('rejects a single value or a map for in', function (mixed $value) {
    $validator = validateFilterSetWith(['field' => 'column_Status', 'value' => $value, 'filter_operator' => 'in']);

    expect($validator->errors()->keys())->toBe(['filter_sets.0.filters.0.value']);
})->with([
    'a single value' => 'open',
    'a map' => [['status' => 'open']],
]);

it('applies the rules of the operation the operator resolves to', function () {
    config()->set('visualizations.filters', [WholeNumberEquals::class, Equals::class]);

    expect(validateFilterSetWith(['field' => 'column_ID', 'value' => 'Ada', 'filter_operator' => 'equals'])->errors()->keys())
        ->toBe(['filter_sets.0.filters.0.value'])
        ->and(validateFilterSetWith(['field' => 'column_ID', 'value' => 7, 'filter_operator' => 'equals'])->passes())
        ->toBeTrue();
});

it('reports only the operator when no operation resolves', function () {
    $validator = validateFilterSetWith(['field' => 'column_ID', 'value' => ['a'], 'filter_operator' => 'between']);

    expect($validator->errors()->keys())->toBe(['filter_sets.0.filters.0.filter_operator']);
});

it('names the filter value when it is not a single value', function () {
    $validator = validateFilterSetWith(['field' => 'column_ID', 'value' => ['a'], 'filter_operator' => 'equals']);

    expect($validator->errors()->first('filter_sets.0.filters.0.value'))
        ->toBe('The filter sets.0.filters.0.value must be a single value.');
});
