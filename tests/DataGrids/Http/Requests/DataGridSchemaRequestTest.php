<?php

use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridSchemaRequest;

test('leaves authorization to the visualization', function () {
    // Visualization::respondWithSchema() checks the visualization's own permission, if it declares one.
    expect((new DataGridSchemaRequest)->authorize())->toBeTrue();
});

test('takes no input', function () {
    expect((new DataGridSchemaRequest)->rules())->toBe([]);
});
