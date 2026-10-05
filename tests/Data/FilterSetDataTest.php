<?php

use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;

/*
 | A filter set is written out in the same snake_case wire shape a request
 | sends it in, with each filter in FilterData's own toArray() shape, so it
 | can be stored, sent back to a client and parsed again.
 */

function filterSetWireShape(): array
{
    return [
        'filters' => [
            ['field' => 'created_at', 'value' => '2026-01-01', 'filter_operator' => 'gte'],
            ['field' => 'status', 'value' => ['open', 'pending'], 'filter_operator' => 'in'],
        ],
        'filter_set_operator' => 'or',
    ];
}

function filterSetOfTwoFilters(): FilterSetData
{
    return new FilterSetData(collect([
        new FilterData('created_at', '2026-01-01', FilterOperator::GREATER_THAN_OR_EQUAL_TO),
        new FilterData('status', ['open', 'pending'], FilterOperator::IN),
    ]), FilterSetOperator::OR);
}

test('toArray writes each filter in its own snake_case toArray shape', function () {
    $filterSet = filterSetOfTwoFilters();

    expect($filterSet->toArray())->toBe([
        'filters' => $filterSet->filters->map(fn (FilterData $filter): array => $filter->toArray())->all(),
        'filter_set_operator' => 'or',
    ]);
    expect($filterSet->toArray())->toBe(filterSetWireShape());
});

test('json encoding toArray emits snake_case filters, not FilterData properties', function () {
    $json = json_encode(filterSetOfTwoFilters()->toArray(), JSON_THROW_ON_ERROR);

    expect(json_decode($json, true))->toBe(filterSetWireShape());
    expect($json)->not->toContain('filterOperator');
});

test('an empty filter set writes an empty filter list', function () {
    expect((new FilterSetData)->toArray())->toBe(['filters' => [], 'filter_set_operator' => 'and']);
});

test('a parsed filter set writes back the shape it was parsed from', function () {
    $filterSets = (new VisualizationData)->parseFilterSets([filterSetWireShape()]);

    expect($filterSets->first()->toArray())->toBe(filterSetWireShape());
});

test('a collection of filter sets converts every set and filter to arrays', function () {
    $filterSets = collect([filterSetOfTwoFilters(), new FilterSetData]);

    expect($filterSets->toArray())->toBe([
        filterSetWireShape(),
        ['filters' => [], 'filter_set_operator' => 'and'],
    ]);
    expect(json_decode($filterSets->toJson(), true))->toBe($filterSets->toArray());
});
