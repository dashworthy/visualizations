<?php

namespace Dashworthy\Visualizations\Data;

use Dashworthy\Visualizations\Enums\SortOperator;
use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, int|string> */
class SortData implements Arrayable
{
    public function __construct(public string $field, public SortOperator $sortOperator) {}

    public static function make(string $field, SortOperator $sortOperator): self
    {
        return new self($field, $sortOperator);
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'sort_operator' => $this->sortOperator->value,
        ];
    }
}
