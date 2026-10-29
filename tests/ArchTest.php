<?php

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Charts\Abstracts\Dataset;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\Contracts\NormalizerContract;
use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\Metrics\Value;
use Dashworthy\Visualizations\VisualizationsServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

$isAbstractClass = function (string $class): bool {
    $reflection = new ReflectionClass($class);

    return $reflection->isAbstract() && ! $reflection->isInterface() && ! $reflection->isTrait();
};

/*
 * Charts, DataGrids and Metrics are the package's modules; every other top-level directory is shared by all three.
 */
$modules = [
    'Dashworthy\Visualizations\Charts',
    'Dashworthy\Visualizations\DataGrids',
    'Dashworthy\Visualizations\Metrics',
];

arch('every class keeps to the package\'s approved directories')
    ->expect('Dashworthy\Visualizations')
    ->toUseApprovedDirectories(
        [
            'Abstracts' => $isAbstractClass,
            'Builders',
            'Charts',
            'Contracts' => fn (string $class): bool => interface_exists($class),
            'Data',
            'DataGrids',
            'Enums' => UnitEnum::class,
            'Events',
            'FilterOperations',
            'FloatingFilters' => FloatingFilter::class,
            'Metrics',
            'Normalizers' => NormalizerContract::class,
            'Query',
            'Rules' => ValidationRule::class,
            'Traits' => fn (string $class): bool => trait_exists($class),
        ],
        beneath: 'Dashworthy\Visualizations',
    )
    // Laravel discovers a package's service provider at its root namespace.
    ->ignoring(VisualizationsServiceProvider::class);

arch('every module keeps to the approved module directories')
    ->expect($modules)
    ->toUseApprovedDirectories(
        [
            'Abstracts' => $isAbstractClass,
            'Columns' => Column::class,
            'Commands' => Command::class,
            'Datasets' => Dataset::class,
            'Enums' => UnitEnum::class,
            'Http' => FormRequest::class,
            'Labels' => Label::class,
        ],
        beneath: 'Dashworthy\Visualizations\*',
    )
    // Apps declare a metric's value as Metrics\Value; moving it would break every metric.
    ->ignoring(Value::class);

arch('every shared class is named for its directory')
    ->expect('Dashworthy\Visualizations')
    ->toUseDirectorySuffixes(
        [
            'Builders' => 'Builder',
            'Data' => 'Data',
            'Normalizers' => 'Normalizer',
            'Query' => 'Query',
            'Rules' => 'Rule',
        ],
        beneath: 'Dashworthy\Visualizations',
    );

arch('every module class is named for its directory')
    ->expect($modules)
    ->toUseDirectorySuffixes(
        [
            'Commands' => 'Command',
            'Http' => 'Request',
            'Labels' => 'Label',
        ],
        beneath: 'Dashworthy\Visualizations\*',
    );

arch('every class has a test at its mirrored path')
    ->expect('Dashworthy\Visualizations')
    ->toHaveTests([
        dirname(__DIR__).'/src' => __DIR__,
    ]);
