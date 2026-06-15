<?php

namespace Dashworthy\Visualizations\Traits;

trait HandlesMetaData
{
    /** @var array<string, mixed> */
    protected array $meta = [];

    public function meta(string $key, mixed $value): static
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function getMeta(string $key): mixed
    {
        return $this->meta[$key] ?? null;
    }
}
