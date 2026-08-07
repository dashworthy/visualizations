<?php

namespace Dashworthy\Visualizations\Tests\Fixtures;

use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;

/**
 * A kind this package does not define, standing in for one an application
 * would add. Nothing in `src/` knows this exists — that is the property the
 * tests using it are checking.
 */
enum CustomVisualizationType: string implements DefinesVisualizationType
{
    case Funnel = 'funnel';

    public function getTypeKey(): string
    {
        return $this->value;
    }
}
