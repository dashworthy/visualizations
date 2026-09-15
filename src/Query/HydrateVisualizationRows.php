<?php

namespace Dashworthy\Visualizations\Query;

use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Columns\HydratedColumn;
use Exception;
use Illuminate\Support\Collection;
use stdClass;

/** Fills a page's hydrated columns, one bulk resolution per column. */
class HydrateVisualizationRows
{
    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  Collection<int, stdClass>  $rows  mutated in place
     * @param  Collection<int, Visualizable>  $visualizables
     * @return Collection<int, stdClass>
     *
     * @throws Exception
     */
    public function handle(Collection $rows, Collection $visualizables): Collection
    {
        foreach ($visualizables as $visualizable) {
            if ($visualizable instanceof HydratedColumn) {
                $this->hydrate($rows, $visualizables, $visualizable);
            }
        }

        return $rows;
    }

    /**
     * @param  Collection<int, stdClass>  $rows
     * @param  Collection<int, Visualizable>  $visualizables
     *
     * @throws Exception
     */
    private function hydrate(Collection $rows, Collection $visualizables, HydratedColumn $column): void
    {
        $hydrator = $column->getHydrator();
        $keyField = $this->keyField($visualizables, $hydrator);
        $field = $column->getField();

        if ($field === $keyField) {
            throw new Exception("Hydrated column '{$field}' collides with the field its hydrator keys on.");
        }

        // Strictly, because the write-back indexes by array-key identity: '01' and 1 compare equal.
        $keys = $rows
            ->map(fn (stdClass $row): mixed => $row->{$keyField} ?? null)
            ->reject(fn (mixed $key): bool => $key === null)
            ->map(fn (mixed $key): int|string => is_int($key) || is_string($key) ? $key : throw new Exception(
                "Field '{$keyField}' holds a ".get_debug_type($key).'; a hydrator can only be keyed by an int or a string.'
            ))
            ->unique(strict: true)
            ->values();

        // An empty page, or one with only null keys, should not cost a query.
        $resolved = $keys->isEmpty() ? [] : $hydrator->resolve($keys);

        foreach ($rows as $row) {
            $key = $row->{$keyField} ?? null;

            $row->{$field} = $key === null ? null : ($resolved[$key] ?? null);
        }
    }

    /**
     * The payload field ('column_ID') behind a hydrator's declared key ('ID').
     *
     * Only what the statement actually selected can key a row. A hydrated column holds no value
     * yet, and a floating filter is never selected at all — GenerateVisualizationQuery leaves it
     * out — so matching one would read a property no row has and null the whole column silently.
     *
     * @param  Collection<int, Visualizable>  $visualizables
     *
     * @throws Exception
     */
    private function keyField(Collection $visualizables, HydratorContract $hydrator): string
    {
        $declaredField = $hydrator->keyedBy();

        $keyColumn = $visualizables->first(
            fn (Visualizable $visualizable): bool => ! $visualizable instanceof FloatingFilter
                && $visualizable->hasExpression()
                && $visualizable->getField() === $visualizable->getFieldPrefix().$declaredField
        );

        if (! $keyColumn instanceof Visualizable) {
            throw new Exception(sprintf(
                "%s keys on '%s', which is not a column on this visualization.",
                $hydrator::class,
                $declaredField,
            ));
        }

        return $keyColumn->getField();
    }
}
