<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Builders\FilterBuilder;
use Dashworthy\Visualizations\Charts\Datasets\Bar;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\Charts\Labels\NullLabel;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Metrics\Value;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('category');
    });

    DB::table('products')->insert([
        ['name' => 'banana', 'category' => 'fruit'],
        ['name' => 'apple', 'category' => 'fruit'],
        ['name' => 'carrot', 'category' => 'vegetable'],
    ]);
});

/**
 * @param  array<int, Visualizable>  $visualizables
 * @return array<int, array<string, mixed>>
 */
function queryProducts(array $visualizables, VisualizationData $visualizationData): array
{
    return productsQuery($visualizables, $visualizationData)
        ->get()
        ->map(fn (object $row): array => (array) $row)
        ->all();
}

/**
 * @param  array<int, Visualizable>  $visualizables
 */
function productsQuery(array $visualizables, VisualizationData $visualizationData): Builder
{
    return GenerateVisualizationQuery::make()->handle(DB::table('products'), collect($visualizables), $visualizationData);
}

test('columns, datasets, labels and values are selected', function (Visualizable $visualizable) {
    expect($visualizable->isSelected())->toBeTrue();
})->with([
    'column' => fn () => Text::make('name', 'name'),
    'dataset' => fn () => Bar::make('SUM(total)', 'total'),
    'label' => fn () => Label::make('name', 'name'),
    'value' => fn () => Value::make('SUM(total)', 'total'),
]);

test('floating filters and the null label are not selected', function (Visualizable $visualizable) {
    expect($visualizable->isSelected())->toBeFalse();
})->with([
    'floating filter' => fn () => DateRange::make('created_at', 'created_at'),
    'null label' => fn () => NullLabel::create(),
]);

test('an aggregate is detected by its function name', function (string $expression, bool $isAggregate) {
    expect(Number::make($expression, 'total')->isHavingRequired())->toBe($isAggregate);
})->with([
    'sum' => ['SUM(total)', true],
    'lowercase count' => ['count(id)', true],
    'space before the parenthesis' => ['COUNT (id)', true],
    'function whose name ends in count' => ['get_discount(price)', false],
    'plain column' => ['total', false],
]);

test('aggregate() overrides the detected aggregate', function () {
    expect(Number::make('SUM(total) OVER ()', 'total')->aggregate(false)->isHavingRequired())->toBeFalse()
        ->and(Number::make('median(total)', 'total')->aggregate()->isHavingRequired())->toBeTrue();
});

test('a column without filtering ignores a filter on it', function () {
    $visualizationData = (new VisualizationData)->addAndFilterSet(
        fn (FilterBuilder $filters) => $filters->equals('column_name', 'apple')
    );

    $rows = queryProducts([Text::make('name', 'name')->withoutFiltering()], $visualizationData);

    expect(array_column($rows, 'column_name'))->toBe(['banana', 'apple', 'carrot']);
});

test('a column without sorting ignores a sort on it', function () {
    $visualizationData = (new VisualizationData)->addSortAsc('column_name');

    expect(productsQuery([Text::make('name', 'name')->withoutSorting()], $visualizationData)->orders)->toBeNull();
});

test('a sort on a floating filter is ignored because it is never selected', function () {
    $visualizationData = (new VisualizationData)->addSortAsc('floating_filter_category');

    $query = productsQuery([Text::make('name', 'name'), DateRange::make('category', 'category')], $visualizationData);

    expect($query->orders)->toBeNull();
});

test('a floating filter still filters', function () {
    $visualizationData = (new VisualizationData)->addAndFilterSet(
        fn (FilterBuilder $filters) => $filters->equals('floating_filter_category', 'vegetable')
    );

    $rows = queryProducts([Text::make('name', 'name'), DateRange::make('category', 'category')], $visualizationData);

    expect($rows)->toBe([['column_name' => 'carrot']]);
});

test('the null label adds nothing to the query', function () {
    $visualizationData = (new VisualizationData)
        ->addAndFilterSet(fn (FilterBuilder $filters) => $filters->equals('label__null', 0))
        ->addSortDesc('label__null');

    $rows = queryProducts([NullLabel::create(), Text::make('name', 'name')], $visualizationData);

    expect($rows)->toBe([['column_name' => 'banana'], ['column_name' => 'apple'], ['column_name' => 'carrot']]);
});
