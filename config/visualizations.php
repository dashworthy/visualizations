<?php

use Dashworthy\Visualizations\Normalizers\BooleanNormalizer;
use Dashworthy\Visualizations\Normalizers\NullNormalizer;

return [
    'normalizers' => [
        BooleanNormalizer::class,
        NullNormalizer::class,
    ],
];
