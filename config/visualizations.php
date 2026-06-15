<?php

use Dashworthy\Visualizations\FilterOperations\Equality\DoesNotEqual;
use Dashworthy\Visualizations\FilterOperations\Equality\Equals;
use Dashworthy\Visualizations\FilterOperations\Equality\GreaterThan;
use Dashworthy\Visualizations\FilterOperations\Equality\GreaterThanOrEqualTo;
use Dashworthy\Visualizations\FilterOperations\Equality\LessThan;
use Dashworthy\Visualizations\FilterOperations\Equality\LessThanOrEqualTo;
use Dashworthy\Visualizations\FilterOperations\Sets\In;
use Dashworthy\Visualizations\FilterOperations\Sets\NotIn;
use Dashworthy\Visualizations\FilterOperations\Text\Contains;
use Dashworthy\Visualizations\FilterOperations\Text\DoesNotContain;
use Dashworthy\Visualizations\FilterOperations\Text\EndsWith;
use Dashworthy\Visualizations\FilterOperations\Text\StartsWith;
use Dashworthy\Visualizations\Normalizers\BooleanNormalizer;
use Dashworthy\Visualizations\Normalizers\NullNormalizer;

return [
    'normalizers' => [
        BooleanNormalizer::class,
        NullNormalizer::class,
    ],

    'filters' => [
        // Equality
        Equals::class,
        DoesNotEqual::class,

        // In
        In::class,
        NotIn::class,

        // Numeric
        GreaterThan::class,
        GreaterThanOrEqualTo::class,
        LessThan::class,
        LessThanOrEqualTo::class,

        // String
        Contains::class,
        DoesNotContain::class,
        EndsWith::class,
        StartsWith::class,
    ],
];
