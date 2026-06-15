<?php

use Illuminate\Support\Facades\File;

test('make datagrid command creates file', function () {
    $this->artisan('make:datagrid', ['name' => 'TestDataGrid'])
        ->assertExitCode(0);

    $expectedPath = app_path('DataGrids/TestDataGrid.php');
    expect($expectedPath)->toBeFile();

    $content = file_get_contents($expectedPath);
    expect($content)->toContain('class TestDataGrid extends DataGrid');
    expect($content)->toContain('namespace App\DataGrids;');
    expect($content)->toContain('use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;');
    expect($content)->toContain('getColumns()');
    expect($content)->toContain('getQuery()');

    unlink($expectedPath);
    if (is_dir(app_path('DataGrids'))) {
        File::deleteDirectory(app_path('DataGrids'));
    }
});

test('make datagrid command replaces name placeholder', function () {
    $this->artisan('make:datagrid', ['name' => 'OrderDataGrid'])
        ->assertExitCode(0);

    $expectedPath = app_path('DataGrids/OrderDataGrid.php');
    expect($expectedPath)->toBeFile();

    $content = file_get_contents($expectedPath);
    expect($content)->not->toContain('DATA_GRID_NAME');
    expect($content)->toContain('class OrderDataGrid extends DataGrid');

    unlink($expectedPath);
    if (is_dir(app_path('DataGrids'))) {
        File::deleteDirectory(app_path('DataGrids'));
    }
});
