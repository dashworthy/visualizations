<?php

use Dashworthy\Visualizations\Traits\HandlesMetaData;

$getTraitMock = fn () => new class
{
    use HandlesMetaData;
};

test('can add and retrieve meta data', function () use ($getTraitMock) {
    $mock = $getTraitMock();
    $mock->meta('key', 'value');
    expect($mock->getMeta('key'))->toBe('value');
});

test('can chain with meta calls', function () use ($getTraitMock) {
    $mock = $getTraitMock();
    $mock->meta('key1', 'value1')->meta('key2', 'value2');
    expect($mock->getMeta('key1'))->toBe('value1');
    expect($mock->getMeta('key2'))->toBe('value2');
});

test('returns null for non existent meta key', function () use ($getTraitMock) {
    $mock = $getTraitMock();
    expect($mock->getMeta('non_existent_key'))->toBeNull();
});
