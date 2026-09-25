<?php

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Query\MariaDbFilterOperation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('filter_rows', function (Blueprint $table): void {
        $table->id();
        $table->string('label')->nullable();
    });

    DB::table('filter_rows')->insert([['label' => 'apple'], ['label' => 'banana'], ['label' => null]]);
});

/**
 * @return array<int, string|null>
 */
function filteredLabels(Visualizable $visualizable, FilterData $filterData): array
{
    $query = DB::table('filter_rows')->select('label')->orderBy('id');

    (new MariaDbFilterOperation)->handle($query, $visualizable, $filterData);

    return $query->pluck('label')->all();
}

test('does not contain binds the column expression for each time it is referenced', function () {
    $visualizable = Text::make('COALESCE(label, ?)', 'label', ['none']);

    // The null row reads as 'none', which contains 'on', so only apple and banana remain
    expect(filteredLabels($visualizable, new FilterData('label', 'on', FilterOperator::STRING_DOES_NOT_CONTAIN)))
        ->toBe(['apple', 'banana']);
});

test('not in with a null excludes the listed values and null', function () {
    expect(filteredLabels(Text::make('label', 'label'), new FilterData('label', ['apple', null], FilterOperator::NOT_IN)))
        ->toBe(['banana']);
});

test('not in with only a null excludes null', function () {
    expect(filteredLabels(Text::make('label', 'label'), new FilterData('label', [null], FilterOperator::NOT_IN)))
        ->toBe(['apple', 'banana']);
});

test('not in treats 0 as a value, not as null', function () {
    DB::table('filter_rows')->insert(['label' => '0']);

    expect(filteredLabels(Text::make('label', 'label'), new FilterData('label', [0], FilterOperator::NOT_IN)))
        ->toBe(['apple', 'banana']);
});
