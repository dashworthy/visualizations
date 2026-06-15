<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\Metrics;

use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Metrics\Value;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueWithFloatingFiltersMetric extends Metric
{
    public function getValue(): Value
    {
        return Value::make('sum(orders.total)', 'revenue')
            ->header('Total Revenue');
    }

    public function getFloatingFilters(): Collection
    {
        return collect([
            DateRange::make('order_date', 'date_range')->header('Date Range'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('orders');
    }
}
