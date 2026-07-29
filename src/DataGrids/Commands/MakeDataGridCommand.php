<?php

namespace Dashworthy\Visualizations\DataGrids\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeDataGridCommand extends GeneratorCommand
{
    protected $signature = 'make:datagrid {name}';

    protected $type = 'data-grid';

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\DataGrids';
    }

    public function getStub(): string
    {
        return __DIR__.'/../../../stubs/make-data-grid.stub';
    }

    public function replaceClass($stub, $name): string
    {
        $stub = parent::replaceClass($stub, $name);

        $name = $this->argument('name');

        return str_replace('DATA_GRID_NAME', $name, $stub);
    }
}
