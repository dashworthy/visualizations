<?php

namespace Dashworthy\Visualizations\FilterOperations;

use Dashworthy\Visualizations\Contracts\FilterOperationContract;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Illuminate\Support\Collection;

/**
 * Picks the configured filter operation for an operator. Validation and the query both resolve through it, so a
 * filter's value is checked against the rules of the operation that will bind it.
 */
class FilterOperationResolver
{
    /** @var Collection<int, FilterOperationContract>|null */
    private ?Collection $filterOperations = null;

    public static function make(): self
    {
        return new self;
    }

    /**
     * The first operation in `visualizations.filters` that can handle the operator, or null when none can.
     */
    public function resolve(FilterOperator $filterOperator): ?FilterOperationContract
    {
        // Resolved once per resolver rather than once per filter
        $this->filterOperations ??= collect(config('visualizations.filters'))
            ->map(fn (string $filterClass): FilterOperationContract => app($filterClass));

        return $this->filterOperations->first(
            fn (FilterOperationContract $filterOperation): bool => $filterOperation->canHandle($filterOperator)
        );
    }
}
