<?php

namespace Dashworthy\Visualizations;

use Dashworthy\Visualizations\Abstracts\Visualization;
use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Charts\Commands\MakeChartCommand;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Commands\MakeDataGridCommand;
use Dashworthy\Visualizations\DataGrids\Commands\MakeHydratorCommand;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Metrics\Commands\MakeMetricCommand;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class VisualizationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('visualizations')
            ->hasConfigFile()
            ->hasCommands([
                MakeDataGridCommand::class,
                MakeChartCommand::class,
                MakeMetricCommand::class,
                MakeHydratorCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        /**
         * Checks the class is a visualization of the expected base, then registers its data and schema routes.
         *
         * @param  class-string<Visualization>  $base
         */
        $registerCoreRoutes = function (string $fqcn, string $base, string $kind): Visualization {
            if (! class_exists($fqcn)) {
                throw new \Exception("Could not find class matching: $fqcn");
            }

            $visualization = new $fqcn;
            if (! $visualization instanceof $base) {
                throw new \Exception("Class $fqcn is not a valid $kind");
            }

            Route::post($visualization->getRoutePath().'/data', [$fqcn, 'handleData'])->name($visualization->getRouteName().'.data');
            Route::post($visualization->getRoutePath().'/schema', [$fqcn, 'handleSchema'])->name($visualization->getRouteName().'.schema');

            return $visualization;
        };

        Route::macro('chart', function (string $chartFQCN) use ($registerCoreRoutes): void {
            $registerCoreRoutes($chartFQCN, Chart::class, 'Chart');
        });

        Route::macro('metric', function (string $metricFQCN) use ($registerCoreRoutes): void {
            $registerCoreRoutes($metricFQCN, Metric::class, 'Metric');
        });

        Route::macro('dataGrid', function (string $dataGridFQCN) use ($registerCoreRoutes): void {
            $dataGrid = $registerCoreRoutes($dataGridFQCN, DataGrid::class, 'DataGrid');

            if (method_exists($dataGridFQCN, 'handleViews')) {
                Route::get($dataGrid->getRoutePath().'/views', [$dataGridFQCN, 'handleViews'])
                    ->name($dataGrid->getRouteName().'.views');
            }

            if (method_exists($dataGridFQCN, 'handleViewStore')) {
                Route::post($dataGrid->getRoutePath().'/views', [$dataGridFQCN, 'handleViewStore'])
                    ->name($dataGrid->getRouteName().'.views.store');
            }

            if (method_exists($dataGridFQCN, 'handleViewDestroy')) {
                Route::delete($dataGrid->getRoutePath().'/views/{view}', [$dataGridFQCN, 'handleViewDestroy'])
                    ->name($dataGrid->getRouteName().'.views.destroy');
            }

            if (method_exists($dataGridFQCN, 'handleExport')) {
                Route::post($dataGrid->getRoutePath().'/export', [$dataGridFQCN, 'handleExport'])
                    ->name($dataGrid->getRouteName().'.export');
            }

            if (method_exists($dataGridFQCN, 'handleExportStatus')) {
                Route::get($dataGrid->getRoutePath().'/exports/{export}', [$dataGridFQCN, 'handleExportStatus'])
                    ->name($dataGrid->getRouteName().'.export.status');
            }

            if (method_exists($dataGridFQCN, 'handleExportDownload')) {
                Route::get($dataGrid->getRoutePath().'/exports/{export}/download', [$dataGridFQCN, 'handleExportDownload'])
                    ->name($dataGrid->getRouteName().'.export.download');
            }
        });
    }
}
