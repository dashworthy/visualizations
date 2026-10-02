<?php

namespace Dashworthy\Visualizations\DataGrids\Columns;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Abstracts\Column;

/** Superseded by declaring any column with a hydrator in place of SQL; kept only until its callers move. */
class HydratedColumn extends Column
{
    /** @param  HydratorContract|class-string<HydratorContract>  $hydrator */
    public static function for(HydratorContract|string $hydrator, string $field): static
    {
        return static::make($hydrator, $field);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $this->columnType = $this->getHydrator()->columnType();

        return parent::toArray();
    }
}
