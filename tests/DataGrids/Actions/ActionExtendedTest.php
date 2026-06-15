<?php

use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGridWithResource;

test('to array returns name and empty meta', function () {
    $action = Action::make('Delete', fn (): null => null);
    $array = $action->toArray();

    expect($array['name'])->toBe('Delete');
    expect($array['meta'])->toEqual([]);
});

test('to array with meta includes meta values', function () {
    $action = Action::make('Delete', fn (): null => null);
    $action->meta('icon', 'trash');
    $action->meta('confirm', true);

    $array = $action->toArray();

    expect($array['name'])->toBe('Delete');
    expect($array['meta']['icon'])->toBe('trash');
    expect($array['meta']['confirm'])->toBeTrue();
});

test('handle single row with model resource', function () {
    DB::table('users')->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $receivedModel = null;
    $action = Action::make('Edit', function ($model) use (&$receivedModel): array {
        $receivedModel = $model;

        return ['edited' => true];
    });

    $grid = new UserDataGridWithResource;
    $result = $action->handle($grid, collect([1]));

    expect($receivedModel)->not->toBeNull();
    expect($receivedModel->name)->toBe('John Doe');
    expect($result)->toEqual([['edited' => true]]);
});

test('handle single row with model resource not found returns empty', function () {
    $action = Action::make('Edit', fn ($model): array => ['edited' => true]);

    $grid = new UserDataGridWithResource;
    $result = $action->handle($grid, collect([999]));

    expect($result)->toEqual([]);
});

test('handle multiple rows with model resource', function () {
    DB::table('users')->insert([
        ['name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $names = [];
    $action = Action::make('Process', function ($model) use (&$names) {
        $names[] = $model->name;

        return $model->name;
    });

    $grid = new UserDataGridWithResource;
    $result = $action->handle($grid, collect([1, 2, 3]));

    expect($result)->toHaveCount(3);
    expect($names)->toContain('John Doe');
    expect($names)->toContain('Jane Doe');
    expect($names)->toContain('Bob Smith');
});

test('handle with null resource processes raw values', function () {
    $grid = Mockery::mock(DataGrid::class);
    $grid->resource = null;

    $action = Action::make('Process', fn ($id): int|float => $id * 2);
    $result = $action->handle($grid, collect([1, 2, 3]));

    expect($result)->toEqual([2, 4, 6]);
});

test('with authorization returns self', function () {
    $action = Action::make('Test', fn (): null => null);
    $result = $action->withAuthorization('some-permission');

    expect($result)->toBe($action);
});
