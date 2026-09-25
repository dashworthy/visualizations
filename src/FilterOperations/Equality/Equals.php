<?php

namespace Dashworthy\Visualizations\FilterOperations\Equality;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;

class Equals extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::EQUALS;
    }

    protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        if ($filterData->value === null) {
            return $visualizable->getFilterWith().' IS NULL';
        }

        return $visualizable->getFilterWith().' = ?';
    }

    protected function buildBindings(Visualizable $visualizable, FilterData $filterData): array
    {
        if ($filterData->value === null) {
            return $visualizable->getFilterWithBindings();
        }

        return parent::buildBindings($visualizable, $filterData);
    }
}
