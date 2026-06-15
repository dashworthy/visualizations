<?php

use Dashworthy\Visualizations\Normalizers\NullNormalizer;

test('converts null string to null', function () {
    $normalizer = new NullNormalizer;
    $next = fn ($value) => $value;

    $result = $normalizer->handle('null', $next);

    expect($result)->toBeNull();
});

test('converts empty string to null', function () {
    $normalizer = new NullNormalizer;
    $next = fn ($value) => $value;

    $result = $normalizer->handle('', $next);

    expect($result)->toBeNull();
});

test('leaves other values unchanged', function () {
    $normalizer = new NullNormalizer;
    $next = fn ($value) => $value;

    $result1 = $normalizer->handle('some string', $next);
    $result2 = $normalizer->handle(123, $next);
    $result3 = $normalizer->handle(true, $next);

    expect($result1)->toBe('some string');
    expect($result2)->toBe(123);
    expect($result3)->toBeTrue();
});
