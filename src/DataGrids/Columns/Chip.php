<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

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
