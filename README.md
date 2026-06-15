![Visualizations](art/banner.svg)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dashworthy/visualizations.svg?style=flat-square)](https://packagist.org/packages/dashworthy/visualizations)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/dashworthy/visualizations/run-tests.yml?branch=1.x&label=tests&style=flat-square)](https://github.com/dashworthy/visualizations/actions?query=workflow%3Arun-tests+branch%3A1.x)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/dashworthy/visualizations/fix-php-code-style-issues.yml?branch=1.x&label=code%20style&style=flat-square)](https://github.com/dashworthy/visualizations/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3A1.x)
[![Total Downloads](https://img.shields.io/packagist/dt/dashworthy/visualizations.svg?style=flat-square)](https://packagist.org/packages/dashworthy/visualizations)

A Laravel package for building data visualizations. Define DataGrids, Charts, and Metrics as PHP classes — the package handles query generation, filtering, sorting, pagination, and schema generation for your front end.

## Installation

```bash
composer require dashworthy/visualizations
php artisan vendor:publish --tag="visualizations-migrations"
php artisan migrate
```

## Quick Example

```php
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
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
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('users');
    }
}
```

```php
// routes/api.php
Route::dataGrid(UserDataGrid::class);
Route::chart(RevenueChart::class);
```

## Documentation

Full documentation is available at [the documentation site](https://visualizations.dashworthy.com).

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
