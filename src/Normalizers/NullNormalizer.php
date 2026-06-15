<?php

namespace Dashworthy\Visualizations\Normalizers;

use Closure;
use Dashworthy\Visualizations\Contracts\NormalizerContract;

class NullNormalizer implements NormalizerContract
{
    public function handle(mixed $value, Closure $next): mixed
    {
        if ($value === 'null' || $value === '') {
            $value = null;
        }

        return $next($value);
    }
}
