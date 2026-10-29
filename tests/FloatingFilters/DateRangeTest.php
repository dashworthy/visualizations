<?php

use Dashworthy\Visualizations\FloatingFilters\DateRange;

it('describes itself as a date range floating filter', function () {
    expect(DateRange::make('users.created_at', 'Created')->toArray())->toBe([
        'field' => 'floating_filter_Created',
        'header' => 'Created',
        'type' => 'date_range',
        'meta' => [],
    ]);
});
