<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\Metrics;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Enums\FloatingFilterType;
use Dashworthy\Visualizations\Metrics\Abstracts\Metric;
use Dashworthy\Visualizations\Metrics\Value;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueWithTotalFloatingFilterMetric extends Metric
{
    public function getValue(): Value
    {
        return Value::make('sum(orders.total)', 'revenue')
            ->header('Total Revenue');
    }

    public function getFloatingFilters(): Collection
    {
        $filter = new class('total', 'total') extends FloatingFilter
        {
            protected FloatingFilterType|string $floatingFilterType = 'numeric';
        };

        return collect([$filter]);
    }

    public function getQuery(): Builder
    {
        return DB::table('orders');
    }
}
