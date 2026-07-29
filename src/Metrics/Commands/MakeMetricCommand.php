<?php

namespace Dashworthy\Visualizations\Metrics\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeMetricCommand extends GeneratorCommand
{
    protected $signature = 'make:metric {name}';

    protected $type = 'metric';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Metrics';
    }

    public function getStub(): string
    {
        return __DIR__.'/../../../stubs/make-metric.stub';
    }

    public function replaceClass($stub, $name): string
    {
        $stub = parent::replaceClass($stub, $name);

        $name = $this->argument('name');

        return str_replace('METRIC_NAME', $name, $stub);
    }
}
