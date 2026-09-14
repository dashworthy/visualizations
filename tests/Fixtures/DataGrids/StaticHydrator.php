<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * A hydrator over a fixed map that records how it was called.
 *
 * There is no query to count, so the call count and recorded keys are what make the once-per-page
 * guarantee assertable.
 */
class StaticHydrator implements HydratorContract
{
    public int $resolveCallCount = 0;

    /** @var list<Collection<int, array-key>> */
    public array $keysSeen = [];

    /**
     * @param  array<array-key, mixed>  $map
     * @param  string  $keyedBy  the declared field name, as a grid author would write it
     * @param  bool  $throws  make resolve() throw, to check the exception is not swallowed
     */
    public function __construct(
        protected array $map = [],
        protected string $keyedBy = 'ID',
        protected bool $throws = false,
    ) {}

    public function keyedBy(): string
    {
        return $this->keyedBy;
    }

    public function columnType(): ColumnType|string
    {
        return ColumnType::Text;
    }

    public function resolve(Collection $keys): array
    {
        $this->resolveCallCount++;
        $this->keysSeen[] = $keys;

        if ($this->throws) {
            throw new RuntimeException('the hydration source is down');
        }

        return $this->map;
    }
}
