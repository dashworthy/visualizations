<?php

use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\ColumnCountingUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\DeclaredColumnsDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\HydratingUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\NonMutatingHydratingUserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserNotesDataGrid;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Rows as they arrive from the query builder: plain objects keyed by prefixed field. */
function dataGridRows(array $ids): Collection
{
    return collect($ids)->map(fn ($id) => (object) ['column_ID' => $id, 'column_Name' => 'n']);
}

function dataGridSeedTwoUsers(): void
{
    DB::table('users')->insert([
        ['name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);
}

/**
 * Seeds $count users with two notes each, plus one carrying none.
 *
 * Notes are keyed off the ids the insert actually produced rather than an assumed 1..N: sqlite's
 * autoincrement does not reset on delete, so assuming would pair each user with another's notes.
 *
 * @return array{first: int, noteless: int}
 */
function dataGridSeedUsersWithNotes(int $count): array
{
    $users = [];

    for ($n = 1; $n <= $count + 1; $n++) {
        $users[] = ['name' => "User {$n}", 'email' => "user{$n}@example.com", 'created_at' => now(), 'updated_at' => now()];
    }

    DB::table('users')->insert($users);

    /** @var list<int> $ids */
    $ids = DB::table('users')->orderBy('id')->pluck('id')->all();
    $noteless = array_pop($ids);

    $notes = [];

    foreach ($ids as $id) {
        $notes[] = ['user_id' => $id, 'body' => "note for {$id}"];
        $notes[] = ['user_id' => $id, 'body' => "second note for {$id}"];
    }

    DB::table('notes')->insert($notes);

    return ['first' => $ids[0], 'noteless' => $noteless];
}

/** @return list<string> every SQL statement the callback issues */
function dataGridQueryLog(Closure $callback): array
{
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $callback();

    return $queries;
}

function dataGridFirstLastRequest(): DataGridDataRequest
{
    return DataGridDataRequest::create('/grid-data', 'GET', [
        'first' => 0,
        'last' => 500,
        'filters' => [],
        'sorts' => [],
    ]);
}

function dataGridPaginatedRequest(): DataGridDataRequest
{
    return DataGridDataRequest::create('/grid-data', 'GET', [
        'per_page' => 250,
        'filters' => [],
        'sorts' => [],
    ]);
}

// hydrate()

test('hydrate ignores columns that are not hydrated', function () {
    $rows = dataGridRows([1]);

    DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        Text::make('users.name', 'Name'),
    ]))->hydrate($rows);

    expect((array) $rows->first())->toBe(['column_ID' => 1, 'column_Name' => 'n']);
});

test('hydrate resolves the declared field to the prefixed one the row carries', function () {
    // keyedBy() returns 'ID'; the row carries 'column_ID'. The author never writes the prefix.
    $hydrator = new StaticHydrator([7 => 'seven'], 'ID');
    $rows = dataGridRows([7]);

    DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        Text::make($hydrator, 'Notes'),
    ]))->hydrate($rows);

    expect($hydrator->keysSeen[0]->all())->toBe([7])
        ->and($rows->first()->column_Notes)->toBe('seven');
});

test('hydrate returns the very collection it was handed', function () {
    $rows = dataGridRows([1]);

    $returned = DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        Text::make(new StaticHydrator([1 => 'first']), 'Notes'),
    ]))->hydrate($rows);

    expect($returned)->toBe($rows);
});

test('hydrate throws when the keyed field is not a column on the grid', function () {
    $grid = DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        Text::make(new StaticHydrator([], 'Nonexistent'), 'Notes'),
    ]));

    expect(fn () => $grid->hydrate(dataGridRows([1])))
        ->toThrow(Exception::class, 'Nonexistent');
});

test('hydrate throws rather than key off a floating filter of the same name', function () {
    // A floating filter is never selected, so keying off one would read a property no row has and
    // null the whole column silently. It has to be the loud failure, not the silent one.
    $hydrator = new StaticHydrator([], 'Joined');

    $grid = DeclaredColumnsDataGrid::with(
        collect([
            Number::make('users.id', 'ID'),
            Text::make($hydrator, 'Notes'),
        ]),
        collect([DateRange::make('DATE(users.created_at)', 'Joined')]),
    );

    expect(fn () => $grid->hydrate(dataGridRows([1])))
        ->toThrow(Exception::class, 'Joined')
        ->and($hydrator->resolveCallCount)->toBe(0);
});

test('hydrate throws when a hydrated column collides with the field it keys on', function () {
    $grid = DeclaredColumnsDataGrid::with(collect([
        Number::make('users.id', 'ID'),
        Text::make(new StaticHydrator([], 'ID'), 'ID'),
    ]));

    expect(fn () => $grid->hydrate(dataGridRows([1])))
        ->toThrow(Exception::class, 'collides');
});

test('hydrate will not key one hydrated column off another', function () {
    $grid = DeclaredColumnsDataGrid::with(collect([
        Text::make('users.name', 'Name'),
        Text::make(new StaticHydrator([], 'Name'), 'ID'),
        Text::make(new StaticHydrator([], 'ID'), 'Notes'),
    ]));

    expect(fn () => $grid->hydrate(dataGridRows([1])))
        ->toThrow(Exception::class, "keys on 'ID'");
});

test('hydrate on its own produces the same hydrated values as the grid', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedTwoUsers();
    $grid = new HydratingUserDataGrid;

    $exported = $grid->hydrate(DB::table('users')->selectRaw('users.id as column_ID')->orderBy('users.id')->get());
    $displayed = $grid->handleData(dataGridFirstLastRequest())->getData(true)['data'];

    expect($exported->pluck('column_Notes')->all())
        ->toBe(array_column($displayed, 'column_Notes'));
});

// handleData(), both branches

test('the first/last branch returns rows carrying the hydrated field', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedTwoUsers();
    $grid = new HydratingUserDataGrid;

    $rows = $grid->handleData(dataGridFirstLastRequest())->getData(true)['data'];

    expect($rows[0]['column_Notes'])->toBe('first note')
        ->and($rows[1]['column_Notes'])->toBe('second note')
        ->and($grid->hydrator()->resolveCallCount)->toBe(1);
});

test('the paginated branch returns rows carrying the hydrated field', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedTwoUsers();
    $grid = new HydratingUserDataGrid;

    $data = $grid->handleData(dataGridPaginatedRequest())->getData(true);

    expect($data['data'][0]['column_Notes'])->toBe('first note')
        ->and($data['data'][1]['column_Notes'])->toBe('second note')
        ->and($data['total'])->toBe(2)
        ->and($grid->hydrator()->resolveCallCount)->toBe(1);
});

test('the paginated branch serves the collection hydrate() returned', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedTwoUsers();

    $data = (new NonMutatingHydratingUserDataGrid)
        ->handleData(dataGridPaginatedRequest())
        ->getData(true);

    expect($data['data'][0]['column_Notes'])->toBe(NonMutatingHydratingUserDataGrid::NOTE);
});

test('the first/last branch serves the collection hydrate() returned', function () {
    // The paginated branch has the same test above. Both need it: hydration writes in place, so
    // either branch would look correct while ignoring what hydrate() returned — and hydrate() is
    // the documented export seam, which a consumer may well override to return fresh rows.
    Gate::shouldReceive('authorize')->never();
    dataGridSeedTwoUsers();

    $data = (new NonMutatingHydratingUserDataGrid)
        ->handleData(dataGridFirstLastRequest())
        ->getData(true);

    expect($data['data'][0]['column_Notes'])->toBe(NonMutatingHydratingUserDataGrid::NOTE);
});

test('a data request asks the author for columns once', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedTwoUsers();
    $grid = new ColumnCountingUserDataGrid;

    $grid->handleData(dataGridFirstLastRequest());

    expect($grid->getColumnsCalls)->toBe(1);
});

test('a grid that hydrates nothing is left alone', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedTwoUsers();

    $rows = (new UserDataGrid)->handleData(dataGridFirstLastRequest())->getData(true)['data'];

    expect($rows[0])->toBe(['column_ID' => 1, 'column_Name' => 'John Doe', 'column_Email' => 'john@example.com']);
});

// Query cost and success criteria, against the real notes table

test('hydration adds exactly one query on the paginated branch', function () {
    // paginate() issues its own count query, so the absolute here is 3, not 2. What hydration
    // costs is the difference against the same grid without it, on the same branch.
    Gate::shouldReceive('authorize')->never();
    dataGridSeedUsersWithNotes(250);

    $hydrating = dataGridQueryLog(fn () => (new UserNotesDataGrid)->handleData(dataGridPaginatedRequest()));
    $plain = dataGridQueryLog(fn () => (new UserDataGrid)->handleData(dataGridPaginatedRequest()));

    expect(count($hydrating) - count($plain))->toBe(1)
        ->and($hydrating)->toHaveCount(3)
        ->and($plain)->toHaveCount(2);
});

test('hydrating a single row costs two queries on the first/last branch', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedUsersWithNotes(1);

    $queries = dataGridQueryLog(fn () => (new UserNotesDataGrid)->handleData(dataGridFirstLastRequest()));

    expect($queries)->toHaveCount(2);
});

test('hydrating 250 rows costs the same two queries on the first/last branch', function () {
    // Same count as the single-row page: the hydrator resolves the whole page at once, so the
    // second query is one whereIn rather than one lookup per row.
    Gate::shouldReceive('authorize')->never();
    dataGridSeedUsersWithNotes(250);

    $response = null;
    $queries = dataGridQueryLog(function () use (&$response) {
        $response = (new UserNotesDataGrid)->handleData(dataGridFirstLastRequest());
    });

    $rows = $response->getData(true)['data'];

    expect($queries)->toHaveCount(2)
        ->and($rows)->toHaveCount(251)
        ->and(collect($rows)->whereNull('column_Notes')->count())->toBe(1);
});

test('the grid and an export ship identical rows', function () {
    Gate::shouldReceive('authorize')->never();
    ['first' => $first, 'noteless' => $noteless] = dataGridSeedUsersWithNotes(3);
    $grid = new UserNotesDataGrid;

    $displayed = $grid->handleData(dataGridFirstLastRequest())->getData(true)['data'];
    $exported = $grid->handleExport(dataGridFirstLastRequest())->getData(true)['data'];

    expect($exported)->toBe($displayed)
        ->and($displayed[0]['column_Notes'])->toBe("note for {$first}; second note for {$first}")
        ->and(collect($displayed)->firstWhere('column_ID', $noteless)['column_Notes'])->toBeNull();
});

test('the hydrated column reaches the schema as text, sortable and filterable both false', function () {
    $notes = collect(UserNotesDataGrid::schema()['columns'])
        ->firstWhere('field', 'column_Notes');

    expect($notes)->not->toBeNull()
        ->and($notes['header'])->toBe('Notes')
        ->and($notes['type'])->toBe('text')
        ->and($notes['is_sortable'])->toBeFalse()
        ->and($notes['is_filterable'])->toBeFalse();
});

test('the generated statement selects the key column and not the hydrated one', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedUsersWithNotes(2);

    $queries = dataGridQueryLog(fn () => (new UserNotesDataGrid)->handleData(dataGridFirstLastRequest()));

    expect($queries[0])->toContain('column_ID')
        ->and($queries[0])->not->toContain('column_Notes');
});

test('a grid that hydrates nothing issues the queries it always did', function () {
    Gate::shouldReceive('authorize')->never();
    dataGridSeedUsersWithNotes(5);

    $queries = dataGridQueryLog(fn () => (new UserDataGrid)->handleData(dataGridFirstLastRequest()));

    expect($queries)->toHaveCount(1);
});
