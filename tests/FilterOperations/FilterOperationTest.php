<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Query\FilterOperation;
use Illuminate\Database\Query\Builder;

afterEach(fn () => FilterOperation::flushMacros());

function mockFilterVisualizable(): Visualizable
{
    $visualizable = Mockery::mock(Visualizable::class);
    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn(['column-binding']);

    return $visualizable;
}

test('every filter operator applies a condition', function (FilterOperator $filterOperator) {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $filterData = new FilterData('key', 'value', $filterOperator);

    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')
        ->once()
        ->with(Mockery::on(fn (string $expression): bool => str_starts_with(ltrim($expression, '('), 'key ')), Mockery::type('array'))
        ->andReturnSelf();

    expect((new FilterOperation)->handle($query, $visualizable, $filterData))->toBe($query);
})->with(FilterOperator::cases());

test('a subclass bound in the container replaces an operator\'s condition', function () {
    app()->bind(FilterOperation::class, fn () => new class extends FilterOperation
    {
        protected function equals(string $column, array $columnBindings, mixed $value): array
        {
            return ["$column <=> ?", [...$columnBindings, $value]];
        }
    });

    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $visualizable->shouldReceive('getFilterWith')->andReturn('key');
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);

    $query->shouldReceive('whereRaw')->once()->with('key <=> ?', ['value'])->andReturnSelf();

    expect(app(FilterOperation::class)->handle($query, $visualizable, new FilterData('key', 'value', FilterOperator::EQUALS)))->toBe($query);
});

test('a macro adds an operator', function () {
    FilterOperation::macro('regexp', fn (string $column, array $columnBindings, mixed $value): array => [
        "$column REGEXP ?", [...$columnBindings, $value],
    ]);

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereRaw')->once()->with('key REGEXP ?', ['column-binding', '^a'])->andReturnSelf();

    expect((new FilterOperation)->handle($query, mockFilterVisualizable(), new FilterData('key', '^a', 'regexp')))->toBe($query);
});

test('a macro named for a built-in operator replaces it', function () {
    FilterOperation::macro(FilterOperator::IN->value, fn (string $column, array $columnBindings, mixed $value): array => [
        "FIND_IN_SET($column, ?)", [...$columnBindings, implode(',', $value)],
    ]);

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereRaw')->once()->with('FIND_IN_SET(key, ?)', ['column-binding', 'a,'])->andReturnSelf();
    $query->shouldNotReceive('orWhereNull');

    (new FilterOperation)->handle($query, mockFilterVisualizable(), new FilterData('key', ['a', null], FilterOperator::IN));
});

test('an operator with no method or macro is rejected', function () {
    (new FilterOperation)->handle(Mockery::mock(Builder::class), mockFilterVisualizable(), new FilterData('key', 'a', 'regexp'));
})->throws(InvalidArgumentException::class, 'No filter operator or macro named [regexp].');

test('operators lists the built-in operators and the macros', function () {
    FilterOperation::macro('regexp', fn () => ['', []]);

    expect(FilterOperation::operators())
        ->toContain(FilterOperator::EQUALS->value, FilterOperator::GREATER_THAN->value, 'regexp')
        ->toHaveCount(count(FilterOperator::cases()) + 1);
});
