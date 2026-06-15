<?php

namespace Dashworthy\Visualizations\Contracts;

use Closure;

interface NormalizerContract
{
    public function handle(mixed $value, Closure $next): mixed;
}
