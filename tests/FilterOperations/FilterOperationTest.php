<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Query\FilterOperation;
use Illuminate\Database\Query\Builder;

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

test('a subclass bound in the container replaces the conditions', function () {
    app()->bind(FilterOperation::class, fn () => new class extends FilterOperation
    {
        protected function compile(Visualizable $visualizable, FilterData $filterData): array
        {
            return ['overridden = ?', [$filterData->value]];
        }
    });

    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);

    $query->shouldReceive('whereRaw')->once()->with('overridden = ?', ['value'])->andReturnSelf();

    expect(app(FilterOperation::class)->handle($query, $visualizable, new FilterData('key', 'value', FilterOperator::EQUALS)))->toBe($query);
});
