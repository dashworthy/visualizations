<?php

namespace Dashworthy\Visualizations\FilterOperations\Equality;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;

class GreaterThan extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::GREATER_THAN;
    }

    protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        return $visualizable->getFilterWith().' > ?';
    }
}
