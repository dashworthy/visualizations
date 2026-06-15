<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\Charts;

use Dashworthy\Visualizations\Charts\Abstracts\Chart;
use Dashworthy\Visualizations\Charts\Datasets\Bar;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueChart extends Chart
{
    public function getLabel(): Label
    {
        return Label::make('order_date', 'date');
    }

    public function getDatasets(): Collection
    {
        return collect([
            Bar::make('SUM(total)', 'revenue')->header('Revenue'),
            Bar::make('SUM(refunds)', 'refunds')->header('Refunds'),
        ]);
    }

    public function getQuery(): Builder
    {
        return DB::table('orders')->groupBy('order_date');
    }
}
