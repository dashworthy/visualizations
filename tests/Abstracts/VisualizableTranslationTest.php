<?php

use Dashworthy\Visualizations\Charts\Datasets\Bar;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Metrics\Value;

beforeEach(function () {
    app()->setLocale('en');
});

afterEach(function () {
    app()->setLocale(config('app.locale'));
    app('translator')->setLoaded([]);
});

it('returns the field unchanged when no translation exists for the current locale', function () {
    // 'Name' is the field alias; getHeader() falls back to it when no header is set and no translation exists
    $column = new Text('table.name', 'Name');

    expect($column->getHeader())->toBe('Name');
});

it('returns the translated field when a translation exists for the current locale', function () {
    app()->setLocale('fr');
    app('translator')->setLoaded(['*' => ['*' => ['fr' => ['Name' => 'Nom']]]]);

    $column = new Text('table.name', 'Name');

    expect($column->getHeader())->toBe('Nom');
});

it('returns the header override even when a translation exists', function () {
    app()->setLocale('fr');
    app('translator')->setLoaded(['*' => ['*' => ['fr' => ['Name' => 'Nom']]]]);

    $column = (new Text('table.name', 'Name'))->header('Custom');

    expect($column->getHeader())->toBe('Custom');
});

it('translates the header on a Value', function () {
    app()->setLocale('fr');
    app('translator')->setLoaded(['*' => ['*' => ['fr' => ['Revenue' => 'Revenu']]]]);

    $value = Value::make('sum(orders.total)', 'Revenue');

    expect($value->getHeader())->toBe('Revenu');
});

it('translates the header on a Bar dataset', function () {
    app()->setLocale('fr');
    app('translator')->setLoaded(['*' => ['*' => ['fr' => ['Sales' => 'Ventes']]]]);

    $dataset = Bar::make('sum(orders.total)', 'Sales');

    expect($dataset->getHeader())->toBe('Ventes');
});

it('translates the header on a Label', function () {
    app()->setLocale('fr');
    app('translator')->setLoaded(['*' => ['*' => ['fr' => ['Date' => 'Date (FR)']]]]);

    $label = Label::make('orders.created_at', 'Date');

    expect($label->getHeader())->toBe('Date (FR)');
});

it('translates the header on a FloatingFilter', function () {
    app()->setLocale('fr');
    app('translator')->setLoaded(['*' => ['*' => ['fr' => ['Created At' => 'Créé le']]]]);

    $filter = DateRange::make('orders.created_at', 'Created At');

    expect($filter->getHeader())->toBe('Créé le');
});
