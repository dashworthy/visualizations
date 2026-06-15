<?php

use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;

it('holds all telemetry properties', function () {
    $event = new VisualizationQueryExecuted(
        visualizationKey: 'grids.users',
        visualizationType: 'datagrid',
        sql: 'select * from `users`',
        durationMs: 12.5,
        rowCount: 3,
    );

    expect($event->visualizationKey)->toBe('grids.users');
    expect($event->visualizationType)->toBe('datagrid');
    expect($event->sql)->toBe('select * from `users`');
    expect($event->durationMs)->toBe(12.5);
    expect($event->rowCount)->toBe(3);
});
