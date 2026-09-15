<?php

namespace Dashworthy\Visualizations\DataGrids\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeHydratorCommand extends GeneratorCommand
{
    protected $signature = 'make:hydrator {name}';

    protected $type = 'hydrator';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Hydrators';
    }

    public function getStub(): string
    {
        return __DIR__.'/../../../stubs/make-hydrator.stub';
    }

    public function replaceClass($stub, $name): string
    {
        $stub = parent::replaceClass($stub, $name);

        $name = $this->argument('name');

        return str_replace('HYDRATOR_NAME', $name, $stub);
    }
}
