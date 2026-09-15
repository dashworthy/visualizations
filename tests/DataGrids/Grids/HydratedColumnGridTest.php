<?php

use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridDataRequest;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserDataGrid;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\UserNotesDataGrid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** @return list<string> every SQL statement the callback issues */
function notesGridQueryLog(Closure $callback): array
{
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $callback();

    return $queries;
}

/**
 * Seeds $count users with two notes each, plus one carrying none.
 *
 * Notes are keyed off the ids the insert actually produced rather than an assumed 1..N: sqlite's
 * autoincrement does not reset on delete, so assuming would pair each user with another's notes.
 *
 * @return array{first: int, noteless: int}
 */
function notesGridSeedUsers(int $count): array
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

function notesGridRequest(): DataGridDataRequest
{
    return DataGridDataRequest::create('/grid-data', 'GET', [
        'first' => 0,
        'last' => 500,
        'filters' => [],
        'sorts' => [],
    ]);
}

test('hydrating a single row costs two queries', function () {
    Gate::shouldReceive('authorize')->never();
    notesGridSeedUsers(1);

    $queries = notesGridQueryLog(fn () => (new UserNotesDataGrid)->handleData(notesGridRequest()));

    expect($queries)->toHaveCount(2);
});

test('hydrating 250 rows costs the same two queries', function () {
    // Same count as the single-row page: the hydrator resolves the whole page at once, so the
    // second query is one whereIn rather than one lookup per row.
    Gate::shouldReceive('authorize')->never();
    notesGridSeedUsers(250);

    $response = null;
    $queries = notesGridQueryLog(function () use (&$response) {
        $response = (new UserNotesDataGrid)->handleData(notesGridRequest());
    });

    $rows = $response->getData(true)['data'];

    expect($queries)->toHaveCount(2)
        ->and($rows)->toHaveCount(251)
        ->and(collect($rows)->whereNull('column_Notes')->count())->toBe(1);
});

test('the grid and an export ship identical rows', function () {
    Gate::shouldReceive('authorize')->never();
    ['first' => $first, 'noteless' => $noteless] = notesGridSeedUsers(3);
    $grid = new UserNotesDataGrid;

    $displayed = $grid->handleData(notesGridRequest())->getData(true)['data'];
    $exported = $grid->handleExport(notesGridRequest())->getData(true)['data'];

    expect($exported)->toBe($displayed)
        ->and($displayed[0]['column_Notes'])->toBe("note for {$first}; second note for {$first}")
        ->and(collect($displayed)->firstWhere('column_ID', $noteless)['column_Notes'])->toBeNull();
});

test('the hydrated column reaches the schema, sortable and filterable both false', function () {
    $notes = collect(UserNotesDataGrid::schema()['columns'])
        ->firstWhere('field', 'column_Notes');

    expect($notes)->not->toBeNull()
        ->and($notes['header'])->toBe('Notes')
        ->and($notes['is_sortable'])->toBeFalse()
        ->and($notes['is_filterable'])->toBeFalse();
});

test('the generated statement selects the key column and not the hydrated one', function () {
    Gate::shouldReceive('authorize')->never();
    notesGridSeedUsers(2);

    $queries = notesGridQueryLog(fn () => (new UserNotesDataGrid)->handleData(notesGridRequest()));

    expect($queries[0])->toContain('column_ID')
        ->and($queries[0])->not->toContain('column_Notes');
});

test('a grid that hydrates nothing issues the queries it always did', function () {
    Gate::shouldReceive('authorize')->never();
    notesGridSeedUsers(5);

    $queries = notesGridQueryLog(fn () => (new UserDataGrid)->handleData(notesGridRequest()));

    expect($queries)->toHaveCount(1);
});
