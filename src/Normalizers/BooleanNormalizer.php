<?php

namespace Dashworthy\Visualizations\Normalizers;

use Closure;
use Dashworthy\Visualizations\Contracts\NormalizerContract;

class BooleanNormalizer implements NormalizerContract
{
    public function handle(mixed $value, Closure $next): mixed
    {
        if ($value === 'true' || $value === 'on') {
            $value = true;
        } elseif ($value === 'false' || $value === 'off') {
            $value = false;
        }

        return $next($value);
    }
}
