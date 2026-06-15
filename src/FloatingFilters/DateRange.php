<?php

namespace Dashworthy\Visualizations\FloatingFilters;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Enums\FloatingFilterType;

class DateRange extends FloatingFilter
{
    protected FloatingFilterType|string $floatingFilterType = FloatingFilterType::DateRange;
}
