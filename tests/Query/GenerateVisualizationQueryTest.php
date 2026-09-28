<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\SortData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Enums\SortOperator;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

it('matches null for an equals filter whose request value is "null"', function () {
    $request = new Request([
        'filter_sets' => [[
            'filter_set_operator' => FilterSetOperator::AND->value,
            'filters' => [['field' => 'column_name', 'value' => 'null', 'filter_operator' => FilterOperator::EQUALS->value]],
        ]],
    ]);

    $query = GenerateVisualizationQuery::make()->handle(
        DB::table('users'),
        collect([Text::make('name', 'name')]),
        VisualizationData::fromRequest($request)
    );

    expect($query->toSql())->toContain('where (name IS NULL)')
        ->and($query->getBindings())->toBe([]);
});
