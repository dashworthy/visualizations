<?php

use Dashworthy\Visualizations\Abstracts\FilterOperation;
use Dashworthy\Visualizations\Abstracts\Visualizable;
use Dashworthy\Visualizations\Data\FilterData;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Normalizers\RelativeDateNormalizer;
use Illuminate\Support\Carbon;

test('converts a relative day count to the date of the earliest day in the window', function (string $value, string $expected) {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $result = (new RelativeDateNormalizer)->handle($value, fn ($value) => $value);

    expect($result)->toBe($expected);
})->with([
    // Today counts as the first of the N days.
    'seven days' => ['-7 days', '2026-09-29'],
    'one day is today' => ['-1 day', '2026-10-05'],
    'one days' => ['-1 days', '2026-10-05'],
    'two day' => ['-2 day', '2026-10-04'],
    'thirty days' => ['-30 days', '2026-09-06'],
    'leading zero' => ['-07 days', '2026-09-29'],
    'upper bound' => ['-365000 days', '1027-06-06'],
]);

test('crosses a month boundary', function () {
    $this->travelTo(Carbon::parse('2026-03-02 08:00:00'));

    $result = (new RelativeDateNormalizer)->handle('-3 days', fn ($value) => $value);

    expect($result)->toBe('2026-02-28');
});

test('crosses a year boundary', function () {
    $this->travelTo(Carbon::parse('2026-01-01 00:00:00'));

    $result = (new RelativeDateNormalizer)->handle('-2 days', fn ($value) => $value);

    expect($result)->toBe('2025-12-31');
});

test('counts days in the app timezone', function () {
    // 03:00 UTC on the 5th is still the evening of the 4th in Chicago.
    config()->set('app.timezone', 'America/Chicago');
    $this->travelTo(Carbon::parse('2026-10-05 03:00:00', 'UTC'));

    $result = (new RelativeDateNormalizer)->handle('-1 day', fn ($value) => $value);

    expect($result)->toBe('2026-10-04');
});

test('returns the date even where DST starts at midnight', function () {
    // Santiago skips from 00:00 to 01:00 on 2026-09-06, so the start of that day is 01:00.
    config()->set('app.timezone', 'America/Santiago');
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00', 'America/Santiago'));

    $result = (new RelativeDateNormalizer)->handle('-30 days', fn ($value) => $value);

    expect($result)->toBe('2026-09-06');
});

test('leaves anything else untouched', function (mixed $value) {
    $this->travelTo(Carbon::parse('2026-10-05 15:30:00'));

    $result = (new RelativeDateNormalizer)->handle($value, fn ($value) => $value);

    expect($result)->toBe($value);
})->with([
    'zero days' => ['-0 days'],
    'zero days with leading zero' => ['-00 days'],
    'future days' => ['7 days'],
    'plus days' => ['+7 days'],
    'weeks' => ['-7 weeks'],
    'months' => ['-1 month'],
    'leading space' => [' -7 days'],
    'trailing space' => ['-7 days '],
    'trailing newline' => ["-7 days\n"],
    'double space' => ['-7  days'],
    'no space' => ['-7days'],
    'capitalised' => ['-7 Days'],
    'fractional' => ['-1.5 days'],
    'beyond upper bound' => ['-365001 days'],
    'overflowing integer' => ['-99999999999999999999 days'],
    'date string' => ['2026-09-29'],
    'today keyword' => ['today'],
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

    expect($result)->toBe('next:2026-09-29');
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
        ->and($operation->getNormalizedValue('-7 days'))->toBe('2026-09-29');
});
