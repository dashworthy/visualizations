<?php

use Dashworthy\Visualizations\Normalizers\BooleanNormalizer;

test('converts true and on strings to true', function () {
    $normalizer = new BooleanNormalizer;
    $next = fn ($value) => $value;

    $result1 = $normalizer->handle('true', $next);
    $result2 = $normalizer->handle('on', $next);

    expect($result1)->toBeTrue();
    expect($result2)->toBeTrue();
});

test('converts false and off strings to false', function () {
    $normalizer = new BooleanNormalizer;
    $next = fn ($value) => $value;

    $result1 = $normalizer->handle('false', $next);
    $result2 = $normalizer->handle('off', $next);

    expect($result1)->toBeFalse();
    expect($result2)->toBeFalse();
});

test('leaves other values unchanged', function () {
    $normalizer = new BooleanNormalizer;
    $next = fn ($value) => $value;

    $result1 = $normalizer->handle('abc', $next);
    $result2 = $normalizer->handle(123, $next);
    $result3 = $normalizer->handle(null, $next);

    expect($result1)->toBe('abc');
    expect($result2)->toBe(123);
    expect($result3)->toBeNull();
});
