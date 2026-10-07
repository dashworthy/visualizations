---
name: visualization-datagrids
description: Build a DataGrid with the dashworthy/visualizations package — generate the class, define typed columns, register the route, and test it with Pest. Use when the user wants a sortable/filterable data table or grid in a Laravel app.
boost-tags: [dashworthy, visualizations, datagrids]
---

# Building DataGrids

A DataGrid turns a query into a sortable, filterable, paginated table. The
package handles filtering, sorting, pagination, and schema generation from the
columns you define.

## 1. Generate

```bash
php artisan make:datagrid UserDataGrid
```

Creates `app/DataGrids/UserDataGrid.php` extending
`Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid`. Implement
`getColumns()` and `getQuery()`.

## 2. Build

Columns are created with `make($expression, $field)`. The first argument is usually the SQL select expression (often a column reference), and the second is the field name. On a DataGrid column, the first argument can instead be a hydrator; see [Hydrated columns](#hydrated-columns) below.

```php
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Columns\Boolean;
use Dashworthy\Visualizations\DataGrids\Columns\DateTime;
use Dashworthy\Visualizations\DataGrids\Columns\Number;
use Dashworthy\Visualizations\DataGrids\Columns\Text;

class UserDataGrid extends DataGrid
{
    public function getColumns(): Collection
    {
        return collect([
            Number::make('users.id', 'ID')->asRowKey(),
            Text::make('users.name', 'Name'),
            Text::make('users.email', 'Email'),
            Boolean::make('users.is_active', 'Active'),
            DateTime::make('users.created_at', 'Joined'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }
}
```

Column types (in `Dashworthy\Visualizations\DataGrids\Columns`): `Text`, `Number`,
`Date`, `DateTime`, `Time`, `Boolean`, `Chip`.

Per-column modifiers: `->asRowKey()`, `->withoutSorting()`, `->withoutFiltering()`,
`->hidden()`, `->withoutExport()`, `->pinLeft()`, `->pinRight()`, `->header('...')`.

Optional overrides: `getFloatingFilters()` for filters on fields not shown as
columns, and `getDefaultSorts()` for the initial sort order.

### Hydrated columns

For a value on a **to-many** source, which a join would fan out into one row per
relation, declare the column with a hydrator in place of its SQL. The value is
filled after the page is fetched, with one `resolve()` call for the whole page. Any
column type works, and it keeps its own type and modifiers:

```php
Number::make('users.id', 'ID')->asRowKey(),
Text::make(UserNotesHydrator::class, 'Notes'),
Date::make(new LastOrderHydrator($tenant), 'Last Order')->displayFormat('Y-m-d'),
```

```bash
php artisan make:hydrator UserNotesHydrator
```

A hydrator implements `Dashworthy\Visualizations\Contracts\HydratorContract`:

- `keyedBy()` names the field of an ordinary column on the same grid, spelled as
  declared (`'ID'`, not `'column_ID'`).
- `resolve(Collection $keys): array` receives every distinct, non-null key on the
  page and returns `key => value`. A missing key becomes `null`. Scope the lookup
  yourself; the package holds no tenant or authorization context.

Rules:

- A hydrated column is never sortable or filterable, because its value does not
  exist when the page is chosen.
- A class-string hydrator is resolved through the container, on first use only.
- Only DataGrid columns can be hydrated. Labels, datasets, floating filters and
  metrics throw.
- An export must call `$grid->hydrate($rows)` to ship the same values the grid
  shows.

## 3. Register the route

```php
// routes/api.php
use App\DataGrids\UserDataGrid;

Route::dataGrid(UserDataGrid::class);
```

This always registers POST `grids/users/data` and `grids/users/schema` (named
`grids.users.data` and `.schema`), plus view/export routes as below. The path
is derived from the class name minus the `DataGrid` suffix. Override
`getRoutePrefix()` to change the `grids` prefix.

**Saved views.** A grid implementing
`Dashworthy\Visualizations\Contracts\HandlesDataGridViews` gets all six views
routes. `{view}` is the raw route segment (a `string`, never model-bound): the
implementor resolves it and scopes it to the viewer and grid itself.

| Method | Verb | URI | Name |
| --- | --- | --- | --- |
| `handleViews(Request): JsonResponse` | GET | `grids/users/views` | `grids.users.views` |
| `handleViewStore(Request): JsonResponse` | POST | `grids/users/views` | `grids.users.views.store` |
| `handleViewUpdate(Request, string $view): JsonResponse` | PATCH | `grids/users/views/{view}` | `grids.users.views.update` |
| `handleViewDefault(Request, string $view): Response` | PUT | `grids/users/views/{view}/default` | `grids.users.views.default` |
| `handleViewClearDefault(Request, string $view): Response` | DELETE | `grids/users/views/{view}/default` | `grids.users.views.clear-default` |
| `handleViewDestroy(Request, string $view): Response` | DELETE | `grids/users/views/{view}` | `grids.users.views.destroy` |

`Response` is Symfony's, so those three may answer 204 No Content. Deprecated:
a grid that does not implement the contract still gets `.views`,
`.views.store` and `.views.destroy` when it defines `handleViews`,
`handleViewStore` or `handleViewDestroy`; it never gets update, default or
clear-default.

**Exports.** Registered when the grid defines the handler method:

| Method | Verb | URI | Name |
| --- | --- | --- | --- |
| `handleExport` | POST | `grids/users/export` | `grids.users.export` |
| `handleExportStatus` | GET | `grids/users/exports/{export}` | `grids.users.export.status` |
| `handleExportDownload` | GET | `grids/users/exports/{export}/download` | `grids.users.export.download` |

## 4. Test with Pest

```bash
composer require dashworthy/pest-plugin-visualizations --dev
```

```php
use function Dashworthy\PestPluginVisualizations\dataGrid;

it('describes its schema', function () {
    dataGrid(UserDataGrid::class)
        ->assertColumnCount(5)
        ->assertHasColumn('Name')
        ->assertColumnIsSortable('Name')
        ->assertColumnIsFilterable('Email');
});

it('returns rows', function () {
    dataGrid(UserDataGrid::class)
        ->usingFilterSets(fn ($data) => $data->addAndFilterSet(/* ... */))
        ->assertRowCount(1)
        ->assertRowMatches(['Name' => 'Andrew']);
});
```

## Rules

- Name the class with a `DataGrid` suffix — the route name depends on it.
- Give exactly one column `->asRowKey()` (typically the id) so the front end can
  identify rows for selection.
- `getQuery()` returns a query `Builder` (`DB::table(...)`). Eloquent queries
  work too — chain `->toBase()` to hand back the underlying query builder
  (e.g. `User::query()->where('is_active', true)->toBase()`).
- Reference fully-qualified columns (`users.email`) so filtering/sorting and any
  joins resolve unambiguously.
