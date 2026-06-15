<?php

use Dashworthy\Visualizations\Charts\Labels\NullLabel;

it('can be created via the create factory method', function () {
    $label = NullLabel::create();

    expect($label)->toBeInstanceOf(NullLabel::class);
});

it('serializes to an empty array', function () {
    $label = NullLabel::create();

    expect($label->toArray())->toBe([]);
});
