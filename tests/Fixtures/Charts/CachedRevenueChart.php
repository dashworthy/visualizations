<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\Charts;

use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Charts\Datasets\Bar;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\Contracts\ShouldCache;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CachedRevenueChart extends Chart implements ShouldCache
{
    public function getLabel(): Label
    {
        return Label::make('orders.created_at', 'Date');
    }

    public function getDatasets(): Collection
    {
        return collect([
            Bar::make('sum(orders.total)', 'Sales'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('orders');
    }
}
