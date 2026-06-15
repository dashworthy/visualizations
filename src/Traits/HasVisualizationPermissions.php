<?php

namespace Dashworthy\Visualizations\Traits;

use Illuminate\Support\Str;

trait HasVisualizationPermissions
{
    public function getPermissionName(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->snake('_')
            ->toString();
    }
}
