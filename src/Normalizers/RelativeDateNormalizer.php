<?php

namespace Dashworthy\Visualizations\Normalizers;

use Closure;
use Dashworthy\Visualizations\Contracts\NormalizerContract;
use Illuminate\Support\Carbon;

/**
 * Turns a relative interval such as "-7 days" or "-3 hours" into the moment Carbon gives it, in the app timezone.
 *
 * Only "-N unit" with N >= 1 and a unit from UNITS, optionally plural, is converted; the value means exactly what
 * Carbon::parse() makes of it, so on 2026-10-05 15:30 "-7 days" is 2026-09-28 and "-1 month" on 2026-03-31 overflows
 * to 2026-03-03. A unit shorter than a day keeps the time ("-3 hours" becomes "2026-10-05 12:30:00"); a day or longer
 * gives the date alone ("2026-09-28"), which compares as the start of that day against a datetime and as itself
 * against a date column stored as text. Anything else passes through untouched, including a count of zero and one
 * above the unit's cap of roughly a thousand years, which could otherwise overflow into a nonsense date.
 */
class RelativeDateNormalizer implements NormalizerContract
{
    private const DATE_TIME = 'Y-m-d H:i:s';

    private const DATE = 'Y-m-d';

    /**
     * Each unit Carbon parses correctly in "-N unit", with the format of the result and the largest N converted.
     *
     * @var array<string, array{0: string, 1: int}>
     */
    public const UNITS = [
        'sec' => [self::DATE_TIME, 31_557_600_000],
        'second' => [self::DATE_TIME, 31_557_600_000],
        'min' => [self::DATE_TIME, 525_960_000],
        'minute' => [self::DATE_TIME, 525_960_000],
        'hour' => [self::DATE_TIME, 8_766_000],
        'day' => [self::DATE, 365_250],
        'weekday' => [self::DATE, 260_000],
        'week' => [self::DATE, 52_180],
        'fortnight' => [self::DATE, 26_090],
        'month' => [self::DATE, 12_000],
        'year' => [self::DATE, 1_000],
    ];

    public function handle(mixed $value, Closure $next): mixed
    {
        $units = implode('|', array_keys(self::UNITS));

        if (is_string($value) && preg_match("/\\A-(\\d+) ({$units})s?\\z/", $value, $matches) === 1) {
            [$format, $cap] = self::UNITS[$matches[2]];

            // A count too large for an int casts to PHP_INT_MAX, so it fails the cap too.
            $count = (int) $matches[1];

            if ($count >= 1 && $count <= $cap) {
                $value = Carbon::parse($value, config('app.timezone'))->format($format);
            }
        }

        return $next($value);
    }
}
