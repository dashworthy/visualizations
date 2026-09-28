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

function parsedFilterValue(object $parser, FilterOperator $filterOperator, mixed $value): mixed
{
    return $parser->parseFilterSets([
        [
            'filter_set_operator' => FilterSetOperator::AND->value,
            'filters' => [
                ['field' => 'column_name', 'value' => $value, 'filter_operator' => $filterOperator->value],
            ],
        ],
    ])->first()->filters->first()->value;
}

test('parseFilterSets normalizes a scalar value', function () use ($getTrait) {
    expect(parsedFilterValue($getTrait(), FilterOperator::EQUALS, 'null'))->toBeNull()
        ->and(parsedFilterValue($getTrait(), FilterOperator::EQUALS, 'true'))->toBeTrue()
        ->and(parsedFilterValue($getTrait(), FilterOperator::NOT_EQUALS, 'off'))->toBeFalse()
        ->and(parsedFilterValue($getTrait(), FilterOperator::GREATER_THAN, '5'))->toBe('5');
});

test('parseFilterSets normalizes each value in a list', function () use ($getTrait) {
    expect(parsedFilterValue($getTrait(), FilterOperator::IN, ['null', 'a', 'on']))->toBe([null, 'a', true]);
});

test('parseFilterSets leaves a text search term as typed', function (FilterOperator $filterOperator) use ($getTrait) {
    expect(parsedFilterValue($getTrait(), $filterOperator, 'on'))->toBe('on')
        ->and(parsedFilterValue($getTrait(), $filterOperator, 'null'))->toBe('null');
})->with([
    FilterOperator::STRING_STARTS_WITH,
    FilterOperator::STRING_CONTAINS,
    FilterOperator::STRING_DOES_NOT_CONTAIN,
    FilterOperator::STRING_ENDS_WITH,
]);

test('parseFilterSets runs the normalizers once per value', function () use ($getTrait) {
    $calls = 0;
    config()->set('visualizations.normalizers', [
        new class($calls)
        {
            public function __construct(private int &$calls) {}

            public function handle(mixed $value, Closure $next): mixed
            {
                $this->calls++;

                return $next($value);
            }
        },
    ]);

    parsedFilterValue($getTrait(), FilterOperator::IN, ['a', 'b', 'c']);

    expect($calls)->toBe(3);
});
