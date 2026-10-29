<?php

use Dashworthy\Visualizations\Metrics\Http\Requests\MetricDataRequest;

test('leaves authorization to the visualization', function () {
    // Visualization::respondWithData() checks the visualization's own permission, if it declares one.
    expect((new MetricDataRequest)->authorize())->toBeTrue();
});

test('takes filters but no sorts', function () {
    // A metric is one value, so there is nothing to sort.
    expect(array_keys((new MetricDataRequest)->rules()))->toBe(['filter_sets', 'filter_sets.*']);
});

test('validates each filter value against its operator', function () {
    $validator = validator(
        ['filter_sets' => [[
            'filter_set_operator' => 'and',
            'filters' => [['field' => 'value_Total', 'filter_operator' => 'notIn', 'value' => 'open']],
        ]]],
        (new MetricDataRequest)->rules(),
    );

    expect($validator->errors()->keys())->toBe(['filter_sets.0.filters.0.value']);
});
