<?php

use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\ColumnCountingUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\HydratingUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\NonMutatingHydratingUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

// Prefixed because a Pest helper is a global function: a second declaration anywhere under tests/
// is a fatal error at collection time, not a failing test.
function hydrationTestUsers(): void
{
    DB::table('users')->insert([
        ['name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);
}

function hydrationFirstLastRequest(): DataGridDataRequest
{
    return DataGridDataRequest::create('/grid-data', 'GET', [
        'first' => 0,
        'last' => 100,
        'filters' => [],
        'sorts' => [],
    ]);
}

function hydrationPaginatedRequest(): DataGridDataRequest
{
    return DataGridDataRequest::create('/grid-data', 'GET', [
        'per_page' => 250,
        'filters' => [],
        'sorts' => [],
    ]);
}

test('the first/last branch returns rows carrying the hydrated field', function () {
    Gate::shouldReceive('authorize')->never();
    hydrationTestUsers();
    $grid = new HydratingUserDataGrid;

    $rows = $grid->handleData(hydrationFirstLastRequest())->getData(true)['data'];

    expect($rows[0]['column_Notes'])->toBe('first note')
        ->and($rows[1]['column_Notes'])->toBe('second note')
        ->and($grid->hydrator()->resolveCallCount)->toBe(1);
});

test('the paginated branch returns rows carrying the hydrated field', function () {
    Gate::shouldReceive('authorize')->never();
    hydrationTestUsers();
    $grid = new HydratingUserDataGrid;

    $data = $grid->handleData(hydrationPaginatedRequest())->getData(true);

    expect($data['data'][0]['column_Notes'])->toBe('first note')
        ->and($data['data'][1]['column_Notes'])->toBe('second note')
        ->and($data['total'])->toBe(2)
        ->and($grid->hydrator()->resolveCallCount)->toBe(1);
});

test('the paginated branch serves the collection hydrate() returned', function () {
    Gate::shouldReceive('authorize')->never();
    hydrationTestUsers();

    $data = (new NonMutatingHydratingUserDataGrid)
        ->handleData(hydrationPaginatedRequest())
        ->getData(true);

    expect($data['data'][0]['column_Notes'])->toBe(NonMutatingHydratingUserDataGrid::NOTE);
});

test('the first/last branch serves the collection hydrate() returned', function () {
    // The paginated branch has the same test above. Both need it: hydration writes in place, so
    // either branch would look correct while ignoring what hydrate() returned — and hydrate() is
    // the documented export seam, which a consumer may well override to return fresh rows.
    Gate::shouldReceive('authorize')->never();
    hydrationTestUsers();

    $data = (new NonMutatingHydratingUserDataGrid)
        ->handleData(hydrationFirstLastRequest())
        ->getData(true);

    expect($data['data'][0]['column_Notes'])->toBe(NonMutatingHydratingUserDataGrid::NOTE);
});

test('hydrate() on its own produces the same hydrated values as the grid', function () {
    Gate::shouldReceive('authorize')->never();
    hydrationTestUsers();
    $grid = new HydratingUserDataGrid;

    $exported = $grid->hydrate(DB::table('users')->selectRaw('users.id as column_ID')->orderBy('users.id')->get());
    $displayed = $grid->handleData(hydrationFirstLastRequest())->getData(true)['data'];

    expect($exported->pluck('column_Notes')->all())
        ->toBe(array_column($displayed, 'column_Notes'));
});

test('a data request asks the author for columns once', function () {
    Gate::shouldReceive('authorize')->never();
    hydrationTestUsers();
    $grid = new ColumnCountingUserDataGrid;

    $grid->handleData(hydrationFirstLastRequest());

    expect($grid->getColumnsCalls)->toBe(1);
});

test('a grid that hydrates nothing is left alone', function () {
    Gate::shouldReceive('authorize')->never();
    hydrationTestUsers();

    $rows = (new UserDataGrid)->handleData(hydrationFirstLastRequest())->getData(true)['data'];

    expect($rows[0])->toBe(['column_ID' => 1, 'column_Name' => 'John Doe', 'column_Email' => 'john@example.com']);
});
