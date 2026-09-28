<?php

use Dashworthy\Visualizations\Enums\FilterOperator;

test('text operators keep their value as typed', function (FilterOperator $filterOperator) {
    expect($filterOperator->normalizesValue())->toBeFalse();
})->with([
    FilterOperator::STRING_STARTS_WITH,
    FilterOperator::STRING_CONTAINS,
    FilterOperator::STRING_DOES_NOT_CONTAIN,
    FilterOperator::STRING_ENDS_WITH,
]);

test('every other operator normalizes its value', function (FilterOperator $filterOperator) {
    expect($filterOperator->normalizesValue())->toBeTrue();
})->with([
    FilterOperator::EQUALS,
    FilterOperator::NOT_EQUALS,
    FilterOperator::IN,
    FilterOperator::NOT_IN,
    FilterOperator::LESS_THAN,
    FilterOperator::LESS_THAN_OR_EQUAL_TO,
    FilterOperator::GREATER_THAN,
    FilterOperator::GREATER_THAN_OR_EQUAL_TO,
]);
