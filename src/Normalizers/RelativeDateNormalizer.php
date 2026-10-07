<?php

namespace Dashworthy\Visualizations\Normalizers;

use Closure;
use Dashworthy\Visualizations\Contracts\NormalizerContract;
use Illuminate\Support\Carbon;

/**
 * Turns a relative day count such as "-7 days" into the date of the earliest day in that window, counting
 * today as the first day: on 2026-10-05, "-7 days" becomes "2026-09-29" and "-1 day" becomes "2026-10-05".
 * Days are counted in the app timezone. The result is a date with no time, so it compares correctly with a
 * date column stored as text and never lands on 01:00 where DST starts at midnight; a datetime column still
 * compares it as the start of that day. Anything else passes through untouched, including a count of zero and
 * one above MAXIMUM_DAYS, which would otherwise overflow into a nonsense date.
 */
class RelativeDateNormalizer implements NormalizerContract
{
    /**
     * Roughly a thousand years, far beyond any real filter.
     */
    public const MAXIMUM_DAYS = 365000;

    public function handle(mixed $value, Closure $next): mixed
    {
        if (is_string($value) && preg_match('/\A-(\d+) days?\z/', $value, $matches) === 1) {
            // A count too large for an int casts to PHP_INT_MAX, so it fails the upper bound too.
            $days = (int) $matches[1];

            if ($days >= 1 && $days <= self::MAXIMUM_DAYS) {
                $value = Carbon::today(config('app.timezone'))
                    ->subDays($days - 1)
                    ->toDateString();
            }
        }

        return $next($value);
    }
}
