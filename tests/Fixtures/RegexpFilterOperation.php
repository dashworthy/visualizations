<?php

namespace Dashworthy\Visualizations\Tests\Fixtures;

use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Query\FilterOperation;

class RegexpFilterOperation extends FilterOperation
{
    public function operators(): array
    {
        return [...parent::operators(), 'regexp'];
    }

    protected function compile(Visualizable $visualizable, FilterData $filterData): array
    {
        return match ($filterData->getOperatorKey()) {
            'regexp' => [$visualizable->getFilterWith().' REGEXP ?', [...$visualizable->getFilterWithBindings(), $filterData->value]],
            default => parent::compile($visualizable, $filterData),
        };
    }
}
