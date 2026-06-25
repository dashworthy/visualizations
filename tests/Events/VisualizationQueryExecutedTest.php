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

it('defaults fromCache to false and accepts telemetry', function () {
    $event = new VisualizationQueryExecuted(
        visualizationKey: 'grids.users',
        visualizationType: 'datagrid',
        sql: 'select * from `users`',
        durationMs: 12.5,
        rowCount: 3,
    );

    expect($event->fromCache)->toBeFalse();
});

it('represents a cache hit with null sql and fromCache true', function () {
    $event = new VisualizationQueryExecuted(
        visualizationKey: 'grids.users',
        visualizationType: 'datagrid',
        sql: null,
        durationMs: 0.4,
        rowCount: 3,
        fromCache: true,
    );

    expect($event->sql)->toBeNull();
    expect($event->fromCache)->toBeTrue();
    expect($event->rowCount)->toBe(3);
});
