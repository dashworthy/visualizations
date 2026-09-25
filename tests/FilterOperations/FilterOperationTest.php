<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Query\FilterOperation;
use Dashworthy\Visualizations\Tests\Fixtures\RegexpFilterOperation;
use Illuminate\Database\Query\Builder;

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
    app()->bind(FilterOperationContract::class, fn () => new class extends FilterOperation
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

    expect(app(FilterOperationContract::class)->handle($query, $visualizable, new FilterData('key', 'value', FilterOperator::EQUALS)))->toBe($query);
});

test('the contract resolves to FilterOperation by default', function () {
    expect(app(FilterOperationContract::class))->toBeInstanceOf(FilterOperation::class);
});

test('a subclass adds an operator through operators() and compile()', function () {
    $filterOperation = new RegexpFilterOperation;

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereRaw')->once()->with('key REGEXP ?', ['column-binding', '^a'])->andReturnSelf();

    expect($filterOperation->operators())->toContain('regexp', FilterOperator::EQUALS->value)
        ->and($filterOperation->handle($query, mockFilterVisualizable(), new FilterData('key', '^a', 'regexp')))->toBe($query);
});

test('an operator the implementation does not know is rejected', function () {
    (new FilterOperation)->handle(Mockery::mock(Builder::class), mockFilterVisualizable(), new FilterData('key', 'a', 'regexp'));
})->throws(InvalidArgumentException::class, 'No filter operator named [regexp].');

test('operators lists every built-in operator', function () {
    expect((new FilterOperation)->operators())
        ->toBe(array_map(fn (FilterOperator $filterOperator): string => $filterOperator->value, FilterOperator::cases()));
});
