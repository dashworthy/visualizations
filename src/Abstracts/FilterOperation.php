<?php

namespace Dashworthy\Visualizations\Abstracts;

use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Pipeline\Pipeline;

abstract class FilterOperation implements FilterOperationContract
{
    public function getNormalizedValue(mixed $value): mixed
    {
        $normalizers = config('visualizations.normalizers');

        /** @var Pipeline $pipeline */
        $pipeline = app(Pipeline::class);

        return $pipeline->send($value)
            ->through($normalizers)
            ->thenReturn();
    }

    public function getQueryMethod(Visualizable $visualizable, FilterSetOperator $filterSetOperator): string
    {
        $queryMethod = $visualizable->isHavingRequired()
            ? 'havingRaw'
            : 'whereRaw';

        if ($filterSetOperator === FilterSetOperator::OR) {
            return 'or'.ucfirst($queryMethod);
        }

        return $queryMethod;
    }
}
