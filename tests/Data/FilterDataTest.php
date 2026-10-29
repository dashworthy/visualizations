<?php

use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;

it('serializes to the wire shape a request sends', function () {
    $filter = new FilterData('column_Status', ['open', 'pending'], FilterOperator::IN);

    expect($filter->toArray())->toBe([
        'field' => 'column_Status',
        'value' => ['open', 'pending'],
        'filter_operator' => 'in',
    ]);
});
