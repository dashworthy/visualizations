<?php

use Dashworthy\Visualizations\Data\FetchedData;

it('holds the payload and its row count', function () {
    $fetched = new FetchedData(['rows' => [['id' => 1], ['id' => 2]]], 2);

    expect($fetched->payload)->toBe(['rows' => [['id' => 1], ['id' => 2]]])
        ->and($fetched->rowCount)->toBe(2);
});
