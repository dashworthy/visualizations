<?php

namespace Dashworthy\Visualizations\Contracts;

/**
 * What kind of visualization a class is.
 *
 * A contract rather than a fixed enum so the set stays open. This package
 * ships `VisualizationType` covering the three kinds it defines, and an
 * application that builds its own kind — a map, a funnel, a heatmap —
 * implements this on its own enum and returns that instead. Nothing here
 * needs to know about it.
 *
 * `getTypeKey()` is the wire format: the value a consumer persists or sends to
 * a front-end. Implementors own their keys and are responsible for keeping
 * them stable and distinct from the ones this package ships.
 */
interface DefinesVisualizationType
{
    public function getTypeKey(): string;
}
