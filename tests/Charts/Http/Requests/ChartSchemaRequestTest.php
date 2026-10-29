<?php

use Dashworthy\Visualizations\Charts\Http\Requests\ChartSchemaRequest;

test('leaves authorization to the visualization', function () {
    // Visualization::respondWithSchema() checks the visualization's own permission, if it declares one.
    expect((new ChartSchemaRequest)->authorize())->toBeTrue();
});

test('takes no input', function () {
    expect((new ChartSchemaRequest)->rules())->toBe([]);
});
