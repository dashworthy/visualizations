<?php

use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Data\FilterSetData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('status');
        $table->string('code')->nullable();
    });

    DB::table('tickets')->insert([
        ['id' => 1, 'status' => 'open', 'code' => 'abc'],
        ['id' => 2, 'status' => 'open', 'code' => 'xyz'],
        ['id' => 3, 'status' => 'open', 'code' => null],
        ['id' => 4, 'status' => 'closed', 'code' => null],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('tickets');
});

/**
 * Runs one AND filter set against the tickets table and returns the matching ids.
 *
 * @param  list<FilterData>  $filters
 * @return list<int>
 */
function ticketIdsMatching(array $filters, ?Text $code = null): array
{
    $data = new VisualizationData;
    $data->filterSets = collect([new FilterSetData(collect($filters), FilterSetOperator::AND)]);

    $visualizables = collect([
        Text::make('id', 'id'),
        Text::make('status', 'status'),
        $code ?? Text::make('code', 'code'),
    ]);

    return GenerateVisualizationQuery::make()
        ->handle(DB::table('tickets'), $visualizables, $data)
        ->orderBy('id')
        ->pluck('column_id')
        ->map(fn ($id): int => (int) $id)
        ->all();
}

it('excludes both the listed values and nulls for notIn with a null in the list', function () {
    $ids = ticketIdsMatching([
        new FilterData('column_code', ['abc', null], FilterOperator::NOT_IN),
    ]);

    expect($ids)->toBe([2]);
});

it('excludes only nulls for notIn with a list of just null', function () {
    $ids = ticketIdsMatching([
        new FilterData('column_code', [null], FilterOperator::NOT_IN),
    ]);

    expect($ids)->toBe([1, 2]);
});

it('keeps the null branch of in inside its AND filter set', function () {
    $ids = ticketIdsMatching([
        new FilterData('column_status', 'open', FilterOperator::EQUALS),
        new FilterData('column_code', ['abc', null], FilterOperator::IN),
    ]);

    expect($ids)->toBe([1, 3]);
});

it('matches only nulls for in with a list of just null', function () {
    $ids = ticketIdsMatching([
        new FilterData('column_code', [null], FilterOperator::IN),
    ]);

    expect($ids)->toBe([3, 4]);
});

it('binds a bound expression once per occurrence for doesNotContain', function () {
    $ids = ticketIdsMatching(
        [new FilterData('column_code', 'a', FilterOperator::STRING_DOES_NOT_CONTAIN)],
        Text::make('COALESCE(code, ?)', 'code', ['aaa']),
    );

    expect($ids)->toBe([2]);
});

it('does not treat a falsy value as null for in', function () {
    $ids = ticketIdsMatching([
        new FilterData('column_code', [0], FilterOperator::IN),
    ]);

    expect($ids)->toBe([]);
});
