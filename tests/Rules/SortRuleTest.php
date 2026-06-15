<?php

use Illuminate\Support\Facades\Validator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\Rules\SortRule;

it('passes with a valid sort rule', function () {
    $data = [
        'sort' => [
            'field' => 'name',
            'sort_operator' => SortOperator::ASC,
        ],
    ];

    $validator = Validator::make($data, [
        'sort' => [new SortRule],
    ]);

    expect($validator->passes())->toBeTrue();
});

it('fails when field is missing', function () {
    $data = [
        'sort' => [
            'sort_operator' => SortOperator::ASC,
        ],
    ];

    $validator = Validator::make($data, [
        'sort' => [new SortRule],
    ]);

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->toArray())->toHaveKey('sort.field');
});

it('fails when sort_operator is invalid', function () {
    $data = [
        'sorts' => [
            [
                'field' => 'name',
                'sort_operator' => 'INVALID',
            ],
        ],
    ];

    $validator = Validator::make($data, [
        'sorts' => ['array'],
        'sorts.*' => ['required', new SortRule],
    ]);

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->toArray())->toHaveKey('sorts.0.sort_operator');
});
