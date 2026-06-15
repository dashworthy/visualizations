<?php

use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Illuminate\Support\Collection;

test('creates grid data from grid data request', function () {
    $request = DataGridDataRequest::create('/grid-data', 'GET', [
        'filter_sets' => [
            [
                'filters' => [
                    ['field' => 'name', 'value' => 'Doe', 'filter_operator' => 'equals'],
                    ['field' => 'age', 'value' => 40, 'filter_operator' => 'lt'],
                ],
                'filter_set_operator' => 'and',
            ],
        ],
        'sorts' => [
            ['field' => 'name', 'sort_operator' => 'asc'],
            ['field' => 'age', 'sort_operator' => 'desc'],
        ],
    ]);

    $gridData = VisualizationData::fromDataGridRequest($request);

    expect($gridData->filterSets)->toBeInstanceOf(Collection::class);
    expect($gridData->filterSets)->toHaveCount(1);
    expect($gridData->filterSets[0])->toBeInstanceOf(FilterSetData::class);

    $filterSet = $gridData->filterSets[0];

    expect($filterSet->filters)->toBeInstanceOf(Collection::class);
    expect($filterSet->filters)->toHaveCount(2);
    expect($filterSet->filters[0])->toBeInstanceOf(FilterData::class);
    expect($filterSet->filters[0]->field)->toBe('name');
    expect($filterSet->filters[0]->value)->toBe('Doe');
    expect($filterSet->filters[0]->filterOperator)->toBe(FilterOperator::EQUALS);
    expect($filterSet->filters[1]->field)->toBe('age');
    expect($filterSet->filters[1]->value)->toBe(40);
    expect($filterSet->filters[1]->filterOperator)->toBe(FilterOperator::LESS_THAN);

    expect($gridData->sorts)->toBeInstanceOf(Collection::class);
    expect($gridData->sorts)->toHaveCount(2);
    expect($gridData->sorts[0])->toBeInstanceOf(SortData::class);
    expect($gridData->sorts[0]->field)->toBe('name');
    expect($gridData->sorts[0]->sortOperator)->toBe(SortOperator::ASC);
    expect($gridData->sorts[1]->field)->toBe('age');
    expect($gridData->sorts[1]->sortOperator)->toBe(SortOperator::DESC);
});
