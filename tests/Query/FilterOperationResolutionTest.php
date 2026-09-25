<?php

use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Query\FilterOperation;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Support\Facades\DB;

it('resolves the filter operation once per query', function () {
    $resolved = 0;
    app()->bind(FilterOperationContract::class, function () use (&$resolved) {
        $resolved++;

        return new FilterOperation;
    });

    $data = new VisualizationData;
    $data->filterSets = collect([new FilterSetData(collect([
        new FilterData('column_name', 'Ada', FilterOperator::EQUALS),
        new FilterData('column_email', 'ada@example.com', FilterOperator::EQUALS),
        new FilterData('column_id', 1, FilterOperator::EQUALS),
    ]), FilterSetOperator::AND)]);

    GenerateVisualizationQuery::make()->handle(
        DB::table('users'),
        collect([Text::make('id', 'id'), Text::make('name', 'name'), Text::make('email', 'email')]),
        $data
    );

    expect($resolved)->toBe(1);
});
