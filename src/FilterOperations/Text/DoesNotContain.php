<?php

namespace Dashworthy\Visualizations\FilterOperations\Text;

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;

class DoesNotContain extends FilterOperation
{
    public function canHandle(FilterOperator $filterOperator): bool
    {
        return $filterOperator === FilterOperator::STRING_DOES_NOT_CONTAIN;
    }

    protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
    {
        $col = $visualizable->getFilterWith();

        return '('.$col.' NOT LIKE ? OR '.$col.' IS NULL)';
    }

    protected function buildBindings(Visualizable $visualizable, FilterData $filterData): array
    {
        return [...$visualizable->getFilterWithBindings(), '%'.$filterData->value.'%'];
    }
}
