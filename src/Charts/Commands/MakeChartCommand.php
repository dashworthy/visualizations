<?php

namespace Dashworthy\Visualizations\Charts\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeChartCommand extends GeneratorCommand
{
    protected $signature = 'make:chart {name}';

    protected $type = 'chart';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Charts';
    }

    public function getStub(): string
    {
        return __DIR__.'/../../../stubs/make-chart.stub';
    }

    public function replaceClass($stub, $name): string
    {
        $stub = parent::replaceClass($stub, $name);

        $name = $this->argument('name');

        return str_replace('CHART_NAME', $name, $stub);
    }
}
