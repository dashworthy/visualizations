<?php

namespace Dashworthy\Visualizations\Abstracts;

use Illuminate\Pipeline\Pipeline;
use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Enums\FilterSetOperator;

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
