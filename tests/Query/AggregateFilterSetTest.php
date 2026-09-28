<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->string('customer');
        $table->string('status');
        $table->integer('total');
    });

    // Totals: alice 220 (paid 120), bob 230 (paid 30), carol 150 (paid 150)
    DB::table('orders')->insert([
        ['customer' => 'alice', 'status' => 'paid', 'total' => 50],
        ['customer' => 'alice', 'status' => 'paid', 'total' => 70],
        ['customer' => 'alice', 'status' => 'refunded', 'total' => 100],
        ['customer' => 'bob', 'status' => 'paid', 'total' => 30],
        ['customer' => 'bob', 'status' => 'refunded', 'total' => 200],
        ['customer' => 'carol', 'status' => 'paid', 'total' => 150],
    ]);
});

/**
 * @return Collection<int, Visualizable>
 */
function customerTotals(): Collection
{
    return collect([
        Text::make('customer', 'customer'),
        Number::make('SUM(total)', 'total'),
        DateRange::make('status', 'status'),
    ]);
}

/**
 * @return array<int, string>
 */
function customersMatching(VisualizationData $visualizationData): array
{
    $query = DB::table('orders')->groupBy('customer')->orderBy('customer');

    return GenerateVisualizationQuery::make()
        ->handle($query, customerTotals(), $visualizationData)
        ->pluck('column_customer')
        ->all();
}

test('an AND filter set filters on an aggregate', function () {
    $visualizationData = (new VisualizationData)->addAndFilterSet(
        fn (FilterBuilder $filters) => $filters->greaterThan('column_total', 200)
    );

    expect(customersMatching($visualizationData))->toBe(['alice', 'bob']);
});

test('an AND filter set filters rows before grouping and aggregates after', function () {
    $visualizationData = (new VisualizationData)->addAndFilterSet(
        fn (FilterBuilder $filters) => $filters
            ->equals('floating_filter_status', 'paid')
            ->greaterThan('column_total', 100)
    );

    // Only paid orders are summed, so bob's 30 falls short while alice's 120 clears it
    expect(customersMatching($visualizationData))->toBe(['alice', 'carol']);
});

test('an OR filter set filters on aggregates', function () {
    $visualizationData = (new VisualizationData)->addOrFilterSet(
        fn (FilterBuilder $filters) => $filters
            ->greaterThan('column_total', 225)
            ->lessThan('column_total', 160)
    );

    expect(customersMatching($visualizationData))->toBe(['bob', 'carol']);
});

test('an OR filter set that mixes an aggregate with a row filter is rejected', function () {
    $visualizationData = (new VisualizationData)->addOrFilterSet(
        fn (FilterBuilder $filters) => $filters
            ->equals('floating_filter_status', 'refunded')
            ->greaterThan('column_total', 200)
    );

    customersMatching($visualizationData);
})->throws(Exception::class, 'cannot OR an aggregate filter with a row filter');
