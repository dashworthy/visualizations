<?php

use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;

it('adds a filter with the operator each method names', function (string $method, FilterOperator $operator) {
    $filters = (new FilterBuilder)->{$method}('column_Name', 'Ada')->getFilters();

    expect($filters)->toHaveCount(1)
        ->and($filters->first())->toEqual(new FilterData('column_Name', 'Ada', $operator));
})->with([
    ['startsWith', FilterOperator::STRING_STARTS_WITH],
    ['contains', FilterOperator::STRING_CONTAINS],
    ['doesNotContain', FilterOperator::STRING_DOES_NOT_CONTAIN],
    ['endsWith', FilterOperator::STRING_ENDS_WITH],
    ['equals', FilterOperator::EQUALS],
    ['notEquals', FilterOperator::NOT_EQUALS],
    ['in', FilterOperator::IN],
    ['notIn', FilterOperator::NOT_IN],
    ['lessThan', FilterOperator::LESS_THAN],
    ['lessThanOrEqualTo', FilterOperator::LESS_THAN_OR_EQUAL_TO],
    ['greaterThan', FilterOperator::GREATER_THAN],
    ['greaterThanOrEqualTo', FilterOperator::GREATER_THAN_OR_EQUAL_TO],
]);

it('keeps filters in the order they were added', function () {
    $filters = (new FilterBuilder)
        ->equals('column_Status', 'open')
        ->greaterThan('column_Total', 10)
        ->getFilters();

    expect($filters->map(fn (FilterData $filter): string => $filter->field)->all())
        ->toBe(['column_Status', 'column_Total']);
});
