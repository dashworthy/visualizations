<?php

use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\Traits\ParsesVisualizationInput;

$getTrait = fn () => new class
{
    use ParsesVisualizationInput;
};

test('parseFilterSets returns empty collection for empty input', function () use ($getTrait) {
    $result = $getTrait()->parseFilterSets([]);
    expect($result)->toHaveCount(0);
});

test('parseFilterSets builds filter sets from array', function () use ($getTrait) {
    $result = $getTrait()->parseFilterSets([
        [
            'filter_set_operator' => FilterSetOperator::AND->value,
            'filters' => [
                ['field' => 'column_name', 'value' => 'foo', 'filter_operator' => FilterOperator::EQUALS->value],
            ],
        ],
    ]);

    expect($result)->toHaveCount(1);
    expect($result->first()->filterOperator)->toBe(FilterSetOperator::AND);
    expect($result->first()->filters)->toHaveCount(1);
    expect($result->first()->filters->first()->field)->toBe('column_name');
    expect($result->first()->filters->first()->value)->toBe('foo');
});

test('parseSorts returns empty collection for empty input', function () use ($getTrait) {
    $result = $getTrait()->parseSorts([]);
    expect($result)->toHaveCount(0);
});

test('parseSorts builds sort data from array', function () use ($getTrait) {
    $result = $getTrait()->parseSorts([
        ['field' => 'column_name', 'sort_operator' => SortOperator::ASC->value],
        ['field' => 'column_other', 'sort_operator' => SortOperator::DESC->value],
    ]);

    expect($result)->toHaveCount(2);
    expect($result->first()->field)->toBe('column_name');
    expect($result->first()->sortOperator)->toBe(SortOperator::ASC);
    expect($result->last()->field)->toBe('column_other');
    expect($result->last()->sortOperator)->toBe(SortOperator::DESC);
});
