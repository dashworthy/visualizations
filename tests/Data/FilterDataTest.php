<?php

use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Http\Request;

/**
 * Swaps the configured normalizers for one that counts its calls.
 */
function countNormalizerCalls(int &$calls): void
{
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
}

test('normalizes a scalar value', function () {
    expect((new FilterData('column_name', 'null', FilterOperator::EQUALS))->value)->toBeNull()
        ->and((new FilterData('column_name', 'true', FilterOperator::EQUALS))->value)->toBeTrue()
        ->and((new FilterData('column_name', 'off', FilterOperator::NOT_EQUALS))->value)->toBeFalse()
        ->and((new FilterData('column_name', '5', FilterOperator::GREATER_THAN))->value)->toBe('5');
});

test('normalizes each value in a list', function () {
    expect((new FilterData('column_name', ['null', 'a', 'on'], FilterOperator::IN))->value)->toBe([null, 'a', true]);
});

test('leaves a text search term as typed', function (FilterOperator $filterOperator) {
    expect((new FilterData('column_name', 'on', $filterOperator))->value)->toBe('on')
        ->and((new FilterData('column_name', 'null', $filterOperator))->value)->toBe('null');
})->with([
    FilterOperator::STRING_STARTS_WITH,
    FilterOperator::STRING_CONTAINS,
    FilterOperator::STRING_DOES_NOT_CONTAIN,
    FilterOperator::STRING_ENDS_WITH,
]);

test('normalizes a filter added through the filter builder', function () {
    $filters = (new FilterBuilder)->equals('column_name', 'null')->getFilters();

    expect($filters->first()->value)->toBeNull();
});

test('runs the normalizers once per value', function () {
    $calls = 0;
    countNormalizerCalls($calls);

    new FilterData('column_name', ['a', 'b', 'c'], FilterOperator::IN);

    expect($calls)->toBe(3);
});

test('runs the normalizers once per value for a filter parsed from a request', function () {
    $calls = 0;
    countNormalizerCalls($calls);

    VisualizationData::fromRequest(new Request([
        'filter_sets' => [[
            'filter_set_operator' => FilterSetOperator::AND->value,
            'filters' => [['field' => 'column_name', 'value' => ['a', 'b', 'c'], 'filter_operator' => FilterOperator::IN->value]],
        ]],
    ]));

    expect($calls)->toBe(3);
});
