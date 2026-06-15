<?php

use Dashworthy\Visualizations\Charts\Datasets\Bar;

it('serializes to array with the bar type', function () {
    $dataset = Bar::make('SUM(total)', 'revenue')->header('Revenue');

    expect($dataset->toArray())->toBe([
        'field' => 'dataset_revenue',
        'header' => 'Revenue',
        'type' => 'bar',
        'meta' => [],
    ]);
});

it('sets the default stack group when stacked', function () {
    $dataset = Bar::make('SUM(total)', 'revenue')->stacked();

    expect($dataset->getMeta('stack'))->toBe('default');
    expect($dataset->toArray()['meta'])->toBe(['stack' => 'default']);
});

it('sets a named stack group', function () {
    $dataset = Bar::make('SUM(total)', 'revenue')->stackGroup('group-a');

    expect($dataset->getMeta('stack'))->toBe('group-a');
    expect($dataset->toArray()['meta'])->toBe(['stack' => 'group-a']);
});

it('allows stackGroup to override stacked', function () {
    $dataset = Bar::make('SUM(total)', 'revenue')
        ->stacked()
        ->stackGroup('custom');

    expect($dataset->getMeta('stack'))->toBe('custom');
});
