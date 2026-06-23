<?php

use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridBulkActionRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridInlineActionRequest;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridSchemaRequest;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGridWithAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function validateDataGridSchema(array $schema): void
{
    expect($schema['columns'])->toHaveCount(3);

    $columns = [
        [
            'field' => 'column_ID',
            'header' => 'ID',
            'type' => ColumnType::Number->value,
            'pin' => 'none',
            'is_row_key' => true,
            'is_sortable' => true,
            'is_filterable' => true,
            'is_hidden' => false,
            'meta' => [],
        ],
        [
            'field' => 'column_Name',
            'header' => 'Name',
            'type' => ColumnType::Text->value,
            'pin' => 'none',
            'is_row_key' => false,
            'is_sortable' => true,
            'is_filterable' => true,
            'is_hidden' => false,
            'meta' => [],
        ],
        [
            'field' => 'column_Email',
            'header' => 'Email',
            'type' => ColumnType::Text->value,
            'pin' => 'none',
            'is_row_key' => false,
            'is_sortable' => true,
            'is_filterable' => true,
            'is_hidden' => false,
            'meta' => [],
        ],
    ];

    foreach ($columns as $column) {
        expect($schema['columns'])->toContain($column);
    }

    $defaultSorts = [['field' => 'ID', 'sort_operator' => 'asc']];
    foreach ($defaultSorts as $sort) {
        expect($schema['default_sorts'])->toContain($sort);
    }

    $bulkActions = [['name' => 'Create', 'meta' => [], 'url' => '/grids/users/actions/bulk/create']];
    foreach ($bulkActions as $action) {
        expect($schema['bulk_actions'])->toContain($action);
    }

    $inlineActions = [['name' => 'Edit', 'meta' => [], 'url' => '/grids/users/actions/inline/edit']];
    foreach ($inlineActions as $action) {
        expect($schema['inline_actions'])->toContain($action);
    }

    $floatingFilters = [['field' => 'floating_filter_Joined On', 'header' => 'Joined On', 'type' => 'date_range', 'meta' => []]];
    foreach ($floatingFilters as $floatingFilter) {
        expect($schema['floating_filters'])->toContain($floatingFilter);
    }
}

test('gets grid data correctly with first last', function () {
    $grid = new UserDataGrid;
    Gate::shouldReceive('authorize')->never();

    DB::table('users')->insert([
        ['name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $request = DataGridDataRequest::create('/grid-data', 'GET', [
        'first' => 0,
        'last' => 100,
        'filters' => [],
        'sorts' => [],
    ]);

    $response = $grid->handleData($request);

    expect($response)->toBeInstanceOf(JsonResponse::class);

    $data = $response->getData(true);
    $rows = $data['data'];

    expect($rows)->toHaveCount(2);
    expect($rows[0]['column_ID'])->toBe(1);
    expect($rows[0]['column_Name'])->toBe('John Doe');
    expect($rows[0]['column_Email'])->toBe('john@example.com');
    expect($rows[1]['column_ID'])->toBe(2);
    expect($rows[1]['column_Name'])->toBe('Jane Doe');
    expect($rows[1]['column_Email'])->toBe('jane@example.com');
    expect($data['first'])->toBe(0);
    expect($data['last'])->toBe(100);
});

test('gets grid data correctly with default pagination', function () {
    $grid = new UserDataGrid;
    Gate::shouldReceive('authorize')->never();

    DB::table('users')->insert([
        ['name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $request = DataGridDataRequest::create('/grid-data', 'GET', [
        'per_page' => 250,
        'filters' => [],
        'sorts' => [],
    ]);

    $response = $grid->handleData($request);

    expect($response)->toBeInstanceOf(JsonResponse::class);

    $data = $response->getData(true);

    expect($data['total'])->toBe(2);
    expect($data['data'])->toHaveCount(2);
    expect($data['data'][0]['column_ID'])->toBe(1);
    expect($data['data'][0]['column_Name'])->toBe('John Doe');
    expect($data['data'][0]['column_Email'])->toBe('john@example.com');
    expect($data['data'][1]['column_ID'])->toBe(2);
    expect($data['data'][1]['column_Name'])->toBe('Jane Doe');
    expect($data['data'][1]['column_Email'])->toBe('jane@example.com');
    expect($data['per_page'])->toBe(250);
});

test('gets grid schema correctly', function () {
    $schema = UserDataGrid::schema();
    validateDataGridSchema($schema);
});

test('gets valid grid schema from endpoint', function () {
    $grid = new UserDataGrid;
    Gate::shouldReceive('authorize')->never();

    $response = $grid->handleSchema(DataGridSchemaRequest::create($grid->getRoutePath(), 'POST'));

    expect($response)->toBeInstanceOf(JsonResponse::class);
    validateDataGridSchema($response->getData(true));
});

test('invokes permission check when permission trait present on data', function () {
    $grid = new UserDataGridWithAuthorization;

    Gate::shouldReceive('authorize')
        ->with($grid->getPermissionName())
        ->andReturn(true);

    $request = DataGridDataRequest::create('/grid-data', 'GET', [
        'first' => 0,
        'last' => 100,
        'filters' => [],
        'sorts' => [],
    ]);

    $response = $grid->handleData($request);

    expect($response)->toBeInstanceOf(JsonResponse::class);
});

test('creates permission name correctly', function () {
    $grid = new UserDataGridWithAuthorization;

    expect($grid->getPermissionName())->toBe('user_data_grid_with_authorization');
});

test('gets data grid key correctly', function () {
    $grid = new UserDataGrid;

    expect($grid->getVisualizationKey())->toBe('grids.users');
});

test('handles inline action correctly', function () {
    $grid = new UserDataGrid;
    Gate::shouldReceive('allows')->andReturn(true);

    $request = DataGridInlineActionRequest::create('/actions', 'POST', ['row_key' => 1]);

    $response = $grid->handleInlineAction($request, 'edit');

    expect($response)->toBeInstanceOf(JsonResponse::class);
    $this->assertEqualsCanonicalizing([['ran' => true]], $response->getData(true));
});

test('handles bulk action correctly', function () {
    $grid = new UserDataGrid;
    Gate::shouldReceive('allows')->andReturn(true);

    $request = DataGridBulkActionRequest::create('/actions', 'POST', ['row_keys' => [1]]);

    $response = $grid->handleBulkAction($request, 'create');

    expect($response)->toBeInstanceOf(JsonResponse::class);
    $this->assertEqualsCanonicalizing([['ran' => true]], $response->getData(true));
});

test('handles inline unauthorized action', function () {
    $grid = new UserDataGrid;
    Gate::shouldReceive('allows')->andReturn(false);

    $request = DataGridInlineActionRequest::create('/actions', 'POST', ['row_key' => 1]);

    expect(fn () => $grid->handleInlineAction($request, 'edit'))
        ->toThrow(HttpException::class, 'Unauthorized action: Edit');
});

test('handles bulk unauthorized action', function () {
    $grid = new UserDataGrid;
    Gate::shouldReceive('allows')->andReturn(false);

    $request = DataGridBulkActionRequest::create('/bulk-actions', 'POST', ['row_keys' => [1]]);

    expect(fn () => $grid->handleBulkAction($request, 'create'))
        ->toThrow(HttpException::class, 'Unauthorized action: Create');
});

test('handles non existent inline action', function () {
    $grid = new UserDataGrid;

    $request = DataGridInlineActionRequest::create('/inline-actions', 'POST', ['row_key' => 1]);

    expect(fn () => $grid->handleInlineAction($request, 'nonexistent-action'))
        ->toThrow(NotFoundHttpException::class);
});

test('handles non existent bulk action', function () {
    $grid = new UserDataGrid;

    $request = DataGridBulkActionRequest::create('/bulk-actions', 'POST', ['row_keys' => [1]]);

    expect(fn () => $grid->handleBulkAction($request, 'nonexistent-action'))
        ->toThrow(NotFoundHttpException::class);
});

test('fires VisualizationQueryExecuted when handleData is called with first/last pagination', function () {
    Event::fake();

    DB::table('users')->insert([
        ['name' => 'Alice', 'email' => 'alice@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Bob', 'email' => 'bob@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $grid = new UserDataGrid;
    $request = DataGridDataRequest::create('/grid-data', 'GET', [
        'first' => 0,
        'last' => 100,
        'filters' => [],
        'sorts' => [],
    ]);

    $grid->handleData($request);

    Event::assertDispatched(
        VisualizationQueryExecuted::class,
        fn ($event) => $event->visualizationKey === 'grids.users'
            && $event->visualizationType === 'datagrid'
            && $event->rowCount === 2
            && $event->durationMs > 0
    );
});

test('fires VisualizationQueryExecuted when handleData is called with default pagination', function () {
    Event::fake();

    DB::table('users')->insert([
        ['name' => 'Alice', 'email' => 'alice@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Bob', 'email' => 'bob@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $grid = new UserDataGrid;
    $request = DataGridDataRequest::create('/grid-data', 'GET', [
        'per_page' => 250,
        'filters' => [],
        'sorts' => [],
    ]);

    $grid->handleData($request);

    Event::assertDispatched(
        VisualizationQueryExecuted::class,
        fn ($event) => $event->visualizationKey === 'grids.users'
            && $event->visualizationType === 'datagrid'
            && $event->rowCount === 2
            && $event->durationMs > 0
    );
});
