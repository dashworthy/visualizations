<?php

use Dashworthy\Visualizations\Charts\Http\Requests\ChartDataRequest;

test('leaves authorization to the visualization', function () {
    // Visualization::respondWithData() checks the visualization's own permission, if it declares one.
    expect((new ChartDataRequest)->authorize())->toBeTrue();
});

test('passes a request with no sorts or filters', function () {
    expect(validator([], (new ChartDataRequest)->rules())->passes())->toBeTrue();
});

test('validates each sort', function () {
    $validator = validator(
        ['sorts' => [['field' => 'dataset_Count', 'sort_operator' => 'sideways']]],
        (new ChartDataRequest)->rules(),
    );

    expect($validator->errors()->keys())->toBe(['sorts.0.sort_operator']);
});

test('validates each filter value against its operator', function () {
    $validator = validator(
        ['filter_sets' => [[
            'filter_set_operator' => 'and',
            'filters' => [['field' => 'label_Day', 'filter_operator' => 'gte', 'value' => ['-7 days', 'today']]],
        ]]],
        (new ChartDataRequest)->rules(),
    );

    expect($validator->errors()->keys())->toBe(['filter_sets.0.filters.0.value']);
});
