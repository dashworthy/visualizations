<?php

use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Enums\SortOperator;

/*
 | A sort is written out in the same snake_case wire shape a request sends it
 | in, including when it sits in a collection, so it can be stored, sent back
 | to a client and parsed again.
 */

test('toArray writes the snake_case wire shape', function () {
    expect(SortData::make('name', SortOperator::DESC)->toArray())
        ->toBe(['field' => 'name', 'sort_operator' => 'desc']);
});

test('a collection of sorts converts every sort to its snake_case shape', function () {
    $sorts = (new VisualizationData)->addSortAsc('name')->addSortDesc('age')->sorts;

    $wireShape = [
        ['field' => 'name', 'sort_operator' => 'asc'],
        ['field' => 'age', 'sort_operator' => 'desc'],
    ];

    expect($sorts->toArray())->toBe($wireShape);
    expect(json_decode($sorts->toJson(), true))->toBe($wireShape);
});
