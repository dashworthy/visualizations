<?php

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Normalizers\RelativeDateNormalizer;
use Illuminate\Support\Carbon;

test('converts a relative interval with Carbon\'s own meaning', function (string $value, string $expected) {
    // A Monday afternoon.
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $result = (new RelativeDateNormalizer)->handle($value, fn ($value) => $value);

    expect($result)->toBe($expected);
})->with([
    // Units shorter than a day keep the time.
    'seconds' => ['-30 seconds', '2026-10-05 15:29:30'],
    'one second' => ['-1 second', '2026-10-05 15:29:59'],
    'sec' => ['-1 sec', '2026-10-05 15:29:59'],
    'secs' => ['-90 secs', '2026-10-05 15:28:30'],
    'minutes' => ['-45 minutes', '2026-10-05 14:45:00'],
    'one minute' => ['-1 minute', '2026-10-05 15:29:00'],
    'min' => ['-1 min', '2026-10-05 15:29:00'],
    'mins' => ['-15 mins', '2026-10-05 15:15:00'],
    'hours' => ['-3 hours', '2026-10-05 12:30:00'],
    'one hour' => ['-1 hour', '2026-10-05 14:30:00'],
    'hours into yesterday' => ['-16 hours', '2026-10-04 23:30:00'],
    'a day of hours' => ['-24 hours', '2026-10-04 15:30:00'],

    // A day and longer give the date alone.
    'days' => ['-7 days', '2026-09-28'],
    'one day' => ['-1 day', '2026-10-04'],
    'one days' => ['-1 days', '2026-10-04'],
    'thirty days' => ['-30 days', '2026-09-05'],
    'leading zero' => ['-07 days', '2026-09-28'],
    'weekdays skip the weekend' => ['-1 weekday', '2026-10-02'],
    'five weekdays' => ['-5 weekdays', '2026-09-28'],
    'weeks' => ['-2 weeks', '2026-09-21'],
    'one week' => ['-1 week', '2026-09-28'],
    'fortnight' => ['-1 fortnight', '2026-09-21'],
    'fortnights' => ['-3 fortnights', '2026-08-24'],
    'months' => ['-3 months', '2026-07-05'],
    'one month' => ['-1 month', '2026-09-05'],
    'years' => ['-2 years', '2024-10-05'],
    'one year' => ['-1 year', '2025-10-05'],

    // Each unit's cap, roughly a thousand years, still converts.
    'second cap' => ['-31557600000 seconds', '1026-09-28 15:30:00'],
    'minute cap' => ['-525960000 minutes', '1026-09-28 15:30:00'],
    'hour cap' => ['-8766000 hours', '1026-09-28 15:30:00'],
    'day cap' => ['-365250 days', '1026-09-28'],
    'weekday cap' => ['-260000 weekdays', '1030-03-01'],
    'week cap' => ['-52180 weeks', '1026-09-18'],
    'fortnight cap' => ['-26090 fortnights', '1026-09-18'],
    'month cap' => ['-12000 months', '1026-10-05'],
    'year cap' => ['-1000 years', '1026-10-05'],
]);

test('crosses a month boundary', function () {
    $this->travelTo(Carbon::parse('2026-03-02 08:00:00'));

    $result = (new RelativeDateNormalizer)->handle('-3 days', fn ($value) => $value);

    expect($result)->toBe('2026-02-27');
});

test('overflows a month into the next, as Carbon does', function () {
    // 2026-02-31 does not exist, so Carbon rolls it over three days into March.
    $this->travelTo(Carbon::parse('2026-03-31 15:30:00'));

    $result = (new RelativeDateNormalizer)->handle('-1 month', fn ($value) => $value);

    expect($result)->toBe('2026-03-03');
});

test('crosses a year boundary', function (string $value, string $expected) {
    $this->travelTo(Carbon::parse('2026-01-05 08:00:00'));

    $result = (new RelativeDateNormalizer)->handle($value, fn ($value) => $value);

    expect($result)->toBe($expected);
})->with([
    'days' => ['-7 days', '2025-12-29'],
    'month' => ['-1 month', '2025-12-05'],
    'hours' => ['-104 hours', '2026-01-01 00:00:00'],
]);

test('counts in the app timezone', function (string $value, string $expected) {
    // 03:00 UTC on the 5th is still 22:00 on the 4th in Chicago.
    config()->set('app.timezone', 'America/Chicago');
    $this->travelTo(Carbon::parse('2026-10-05 03:00:00', 'UTC'));

    $result = (new RelativeDateNormalizer)->handle($value, fn ($value) => $value);

    expect($result)->toBe($expected);
})->with([
    'day' => ['-1 day', '2026-10-03'],
    'hours' => ['-3 hours', '2026-10-04 19:00:00'],
]);

test('leaves anything else untouched', function (mixed $value) {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $result = (new RelativeDateNormalizer)->handle($value, fn ($value) => $value);

    expect($result)->toBe($value);
})->with([
    'zero days' => ['-0 days'],
    'zero days with leading zero' => ['-00 days'],
    'zero hours' => ['-0 hours'],
    'zero months' => ['-0 months'],
    'future days' => ['7 days'],
    'plus days' => ['+7 days'],
    'leading space' => [' -7 days'],
    'trailing space' => ['-7 days '],
    'trailing newline' => ["-7 days\n"],
    'double space' => ['-7  days'],
    'no space' => ['-7days'],
    'capitalised' => ['-7 Days'],
    'fractional' => ['-1.5 days'],
    'double plural' => ['-7 dayss'],
    'two intervals' => ['-1 day -2 hours'],
    'ago' => ['7 days ago'],
    // Carbon reads "hr" and "decade" as something else and leaves the time unchanged.
    'hr' => ['-3 hr'],
    'decade' => ['-1 decade'],
    // Carbon cannot parse these.
    'quarters' => ['-1 quarter'],
    'decades' => ['-2 decades'],
    'century' => ['-1 century'],
    // Below a second, which a Y-m-d H:i:s value cannot hold.
    'milliseconds' => ['-500 milliseconds'],
    'ms' => ['-500 ms'],
    'microseconds' => ['-500 microseconds'],
    'usec' => ['-500 usec'],
    // PHP's misspelt alias for fortnight.
    'forthnight' => ['-1 forthnight'],
    'weekday name' => ['-1 monday'],
    'second beyond cap' => ['-31557600001 seconds'],
    'minute beyond cap' => ['-525960001 minutes'],
    'hour beyond cap' => ['-8766001 hours'],
    'day beyond cap' => ['-365251 days'],
    'weekday beyond cap' => ['-260001 weekdays'],
    'week beyond cap' => ['-52181 weeks'],
    'fortnight beyond cap' => ['-26091 fortnights'],
    'month beyond cap' => ['-12001 months'],
    'year beyond cap' => ['-1001 years'],
    'overflowing integer' => ['-99999999999999999999 days'],
    'date string' => ['2026-09-29'],
    'today keyword' => ['today'],
    'yesterday keyword' => ['yesterday'],
    'empty string' => [''],
    'integer' => [7],
    'negative integer' => [-7],
    'float' => [1.5],
    'null' => [null],
    'boolean' => [true],
    'array' => [['-7 days']],
]);

test('passes the converted value to the next normalizer', function () {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $result = (new RelativeDateNormalizer)->handle('-7 days', fn ($value) => "next:{$value}");

    expect($result)->toBe('next:2026-09-28');
});

test('runs as a default normalizer', function () {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $operation = new class extends FilterOperation
    {
        public function canHandle(FilterOperator $filterOperator): bool
        {
            return false;
        }

        protected function buildExpression(Visualizable $visualizable, FilterData $filterData): string
        {
            return '';
        }
    };

    expect(config('visualizations.normalizers'))->toContain(RelativeDateNormalizer::class)
        ->and($operation->getNormalizedValue('-7 days'))->toBe('2026-09-28');
});
