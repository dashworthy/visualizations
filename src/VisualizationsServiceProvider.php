<?php

namespace Dashworthy\Visualizations;

use Illuminate\Support\Facades\Route;
use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Charts\Commands\MakeChartCommand;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Commands\MakeDataGridCommand;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Metrics\Commands\MakeMetricCommand;
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
            ]);
    }

    public function packageRegistered(): void
    {
        Route::macro('dataGrid', function (string $dataGridFQCN): void {
            if (! class_exists($dataGridFQCN)) {
                throw new \Exception("Could not find class matching: $dataGridFQCN");
            }

            $dataGrid = new $dataGridFQCN;
            if (! $dataGrid instanceof DataGrid) {
                throw new \Exception("Class $dataGridFQCN is not a valid DataGrid");
            }

            Route::post($dataGrid->getRoutePath().'/data', [$dataGridFQCN, 'handleData'])->name($dataGrid->getRouteName().'.data');
            Route::post($dataGrid->getRoutePath().'/schema', [$dataGridFQCN, 'handleSchema'])->name($dataGrid->getRouteName().'.schema');
            Route::post($dataGrid->getRoutePath().'/actions/inline', [$dataGridFQCN, 'handleInlineAction'])->name($dataGrid->getRouteName().'.actions.inline');
            Route::post($dataGrid->getRoutePath().'/actions/bulk', [$dataGridFQCN, 'handleBulkAction'])->name($dataGrid->getRouteName().'.actions.bulk');

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

        Route::macro('chart', function (string $chartFQCN): void {
            if (! class_exists($chartFQCN)) {
                throw new \Exception("Could not find class matching: $chartFQCN");
            }

            $chart = new $chartFQCN;
            if (! $chart instanceof Chart) {
                throw new \Exception("Class $chartFQCN is not a valid Chart");
            }

            Route::post($chart->getRoutePath().'/data', [$chartFQCN, 'handleData'])->name($chart->getRouteName().'.data');
            Route::post($chart->getRoutePath().'/schema', [$chartFQCN, 'handleSchema'])->name($chart->getRouteName().'.schema');
        });

        Route::macro('metric', function (string $metricFQCN): void {
            if (! class_exists($metricFQCN)) {
                throw new \Exception("Could not find class matching: $metricFQCN");
            }

            $metric = new $metricFQCN;
            if (! $metric instanceof Metric) {
                throw new \Exception("Class $metricFQCN is not a valid Metric");
            }

            Route::post($metric->getRoutePath().'/data', [$metricFQCN, 'handleData'])->name($metric->getRouteName().'.data');
            Route::post($metric->getRoutePath().'/schema', [$metricFQCN, 'handleSchema'])->name($metric->getRouteName().'.schema');
        });
    }
}
