<?php

use Dashworthy\Visualizations\Metrics\Http\Requests\MetricSchemaRequest;

test('leaves authorization to the visualization', function () {
    // Visualization::respondWithSchema() checks the visualization's own permission, if it declares one.
    expect((new MetricSchemaRequest)->authorize())->toBeTrue();
});

test('takes no input', function () {
    expect((new MetricSchemaRequest)->rules())->toBe([]);
});
