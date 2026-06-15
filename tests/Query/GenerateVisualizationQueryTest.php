<?php

use Illuminate\Database\Query\Builder;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;

it('applies filters and sorts to query', function () {
    $query = Mockery::mock(Builder::class);
    $visualizable = Mockery::mock(Visualizable::class);
    $visualizable->shouldReceive('getSelectWith')->andReturn('test_column');
    $visualizable->shouldReceive('getFilterWith')->andReturn('test_column');
    $visualizable->shouldReceive('getField')->andReturn('test_column');
    $visualizable->shouldReceive('getSelectWithBindings')->andReturn([]);
    $visualizable->shouldReceive('getFilterWithBindings')->andReturn([]);
    $visualizable->shouldReceive('isHavingRequired')->andReturn(false);

    $filterData = new FilterData('test_column', 'test_value', FilterOperator::EQUALS);
    $filterSetData = new FilterSetData(collect([$filterData]), FilterSetOperator::AND);
    $sortData = new SortData('test_column', SortOperator::ASC);

    $gridData = new VisualizationData(collect([$filterSetData]), collect([$sortData]));

    $query->shouldReceive('where')->with(Mockery::on(function ($closure) use ($query): true {
        $closure($query);

        return true;
    }))->andReturnSelf();
    $query->shouldReceive('whereRaw')->with('test_column = ?', ['test_value'])->andReturnSelf();
    $query->shouldReceive('orderBy')->with('test_column', 'asc')->andReturnSelf();
    $query->shouldReceive('selectRaw')->with('test_column as `test_column`', [])->andReturnSelf();

    $action = GenerateVisualizationQuery::make();
    $result = $action->handle($query, collect([$visualizable]), $gridData);

    expect($result)->toBe($query);
});
