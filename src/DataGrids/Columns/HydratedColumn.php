<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use LogicException;

/**
 * A column filled after the page is fetched, rather than selected alongside it.
 *
 * For a value on a **to-many** source, which a join would fan out into one row per relation,
 * corrupting pagination and every aggregate in the select. (A to-one join is fine as an ordinary
 * column.) It carries a {@see HydratorContract} instead of SQL, so it contributes nothing to the
 * statement, is never sortable or filterable, and still appears in the schema for rendering.
 *
 * The hydrator's keyedBy() names the field of an ordinary column on the same grid, spelled as the
 * grid declared it — 'ID', not the prefixed payload key 'column_ID'. Declare both:
 *
 *     Number::make('orders.id', 'ID'),                          // keyedBy() returns 'ID'
 *     HydratedColumn::for(OrderNotesHydrator::class, 'Notes'),
 */
class HydratedColumn extends Column
{
    /** @var HydratorContract|class-string<HydratorContract> */
    protected HydratorContract|string $hydrator;

    /** Never sortable: the value does not exist when the page is chosen, so it could not be honoured. */
    protected bool $isSortable = false;

    /** Never filterable, for the same reason. */
    protected bool $isFilterable = false;

    /** No SQL to select, and none for a sort or filter to resolve against. */
    public function hasExpression(): bool
    {
        return false;
    }

    /**
     * Named for() because Visualizable::make() is final and takes a SQL expression — the one thing
     * a hydrated column has not got.
     *
     * @param  HydratorContract|class-string<HydratorContract>  $hydrator  a class-string to have it
     *                                                                     resolved from the container, or a ready instance
     */
    public static function for(HydratorContract|string $hydrator, string $field): static
    {
        $column = new static('', $field);
        $column->hydrator = $hydrator;

        return $column;
    }

    /**
     * The hydrator, resolving a class-string through the container on first use and keeping it.
     *
     * Lazily, because getColumns() runs on every request including those that never hydrate.
     *
     * @throws LogicException when built through the inherited make() or new, which cannot supply a
     *                        hydrator. Both are final upstream, so neither can be hidden.
     */
    public function getHydrator(): HydratorContract
    {
        if (! isset($this->hydrator)) {
            throw new LogicException(sprintf(
                '%s must be declared with %s::for($hydrator, $field); it was built without a hydrator.',
                static::class,
                class_basename(static::class),
            ));
        }

        if ($this->hydrator instanceof HydratorContract) {
            return $this->hydrator;
        }

        /** @var HydratorContract $resolved */
        $resolved = app($this->hydrator);

        return $this->hydrator = $resolved;
    }

    /**
     * Settles the type, which only the hydrator knows, then lets the parent serialise.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $this->columnType = $this->getHydrator()->columnType();

        return parent::toArray();
    }
}
