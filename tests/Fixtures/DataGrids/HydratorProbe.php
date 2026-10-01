<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

/**
 * A dependency for CountingHydrator to be constructed with.
 *
 * Its only job is to be resolvable by the container without configuration, so a test can prove a
 * hydrator named by class-string really is built through the container rather than instantiated
 * directly — a hydrator with constructor dependencies is the reason the class-string form exists.
 */
class HydratorProbe
{
    //
}
