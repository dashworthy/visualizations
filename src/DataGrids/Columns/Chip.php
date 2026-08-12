<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Closure;
use Dashworthy\Visualizations\DataGrids\Abstracts\Column;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Dashworthy\Visualizations\DataGrids\Enums\Severity;

/**
 * Class Chip
 *
 * Represents a column of type "Chip" in the DataGrid.
 * Provides methods to configure severity levels and default severity values.
 */
class Chip extends Column
{
    /**
     * The type of the column.
     */
    protected ColumnType|string $columnType = ColumnType::Chip;

    /**
     * A deferred severity map, resolved when the column is serialised.
     *
     * @var (Closure(): array<string, Severity>)|null
     */
    protected ?Closure $severityResolver = null;

    /**
     * Sets the severity levels for the column.
     *
     * @param  array<string, Severity>  $severity  An associative array mapping keys to Severity values.
     * @return $this
     */
    public function severity(array $severity): static
    {
        $this->meta('severity', $severity);

        return $this;
    }

    /**
     * Defer the severity map to a callback run when the column is serialised.
     *
     * severity() takes its map eagerly, so a map read from the database is
     * built by every caller of the grid's getColumns() — including the data
     * path, which fetches rows on every load, sort, filter and page but never
     * serialises the column. Passing the map as a callback here moves that work
     * to toArray(), the one place the map is actually emitted, so only the
     * schema payload pays for it.
     *
     * @param  Closure(): array<string, Severity>  $resolver
     * @return $this
     */
    public function severityUsing(Closure $resolver): static
    {
        $this->severityResolver = $resolver;

        return $this;
    }

    /**
     * Serialise the column, resolving a deferred severity map first.
     *
     * Runs the severityUsing() callback, if one was given, exactly once at the
     * moment of serialisation and folds its result in through severity(), so the
     * emitted payload is identical to an eagerly configured map.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->severityResolver instanceof Closure) {
            $this->severity(($this->severityResolver)());
        }

        return parent::toArray();
    }

    /**
     * Sets the default severity for the column.
     *
     * @param  Severity  $severity  The default Severity value.
     */
    public function withDefaultSeverity(Severity $severity): self
    {
        $this->meta('default_severity', $severity);

        return $this;
    }

    /**
     * Sets the default severity to "INFO".
     */
    public function defaultsToInfoSeverity(): self
    {
        return $this->withDefaultSeverity(Severity::INFO);
    }

    /**
     * Sets the default severity to "WARNING".
     */
    public function defaultsToWarningSeverity(): self
    {
        return $this->withDefaultSeverity(Severity::WARNING);
    }

    /**
     * Sets the default severity to "DANGER".
     */
    public function defaultsToDangerSeverity(): self
    {
        return $this->withDefaultSeverity(Severity::DANGER);
    }

    /**
     * Sets the default severity to "PRIMARY".
     */
    public function defaultsToPrimarySeverity(): self
    {
        return $this->withDefaultSeverity(Severity::PRIMARY);
    }

    /**
     * Sets the default severity to "SECONDARY".
     */
    public function defaultsToSecondarySeverity(): self
    {
        return $this->withDefaultSeverity(Severity::SECONDARY);
    }

    /**
     * Sets the default severity to "CONTRAST".
     */
    public function defaultsToContrastSeverity(): self
    {
        return $this->withDefaultSeverity(Severity::CONTRAST);
    }
}
