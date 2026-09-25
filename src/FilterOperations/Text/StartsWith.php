<?php

namespace Dashworthy\Visualizations\FilterOperations\Text;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;

class StartsWith extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::STRING_STARTS_WITH;
    }

    protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        return $visualizable->getFilterWith().' LIKE ?';
    }

    protected function buildBindings(Visualizable $visualizable, FilterData $filterData): array
    {
        return [...$visualizable->getFilterWithBindings(), $filterData->value.'%'];
    }
}
