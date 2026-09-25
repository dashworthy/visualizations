<?php

use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Query\FilterOperation;
use Dashworthy\Visualizations\Rules\FilterSetRule;
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

it('accepts a macro operator and rejects an unknown one', function () {
    $validate = fn (string $filterOperator): bool => Validator::make(
        ['filter_sets' => [[
            'filter_set_operator' => FilterSetOperator::AND->value,
            'filters' => [['field' => 'name', 'value' => 'a', 'filter_operator' => $filterOperator]],
        ]]],
        ['filter_sets.*' => [new FilterSetRule]]
    )->passes();

    expect($validate('regexp'))->toBeFalse();

    FilterOperation::macro('regexp', fn () => ['', []]);

    expect($validate('regexp'))->toBeTrue()
        ->and($validate(FilterOperator::EQUALS->value))->toBeTrue();

    FilterOperation::flushMacros();
});
