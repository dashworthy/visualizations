<?php

namespace Dashworthy\Visualizations\Enums;

/**
 * What kind of visualization a class is.
 *
 * A consumer that needs to branch on this — a widget registry storing the kind
 * alongside the class, a renderer choosing a component — should ask the
 * visualization through `getVisualizationType()` rather than testing it against
 * `DataGrid`, `Chart` and `Metric` with `instanceof`. An `instanceof` ladder
 * forces every consumer to know all three base classes, and silently stops
 * matching when a fourth kind is added here.
 *
 * The values are the wire format: they are what a consumer persists or sends to
 * a front-end, so they are stable and must not be renamed without treating it
 * as a breaking change.
 */
enum VisualizationType: string
{
    case DataGrid = 'datagrid';
    case Chart = 'chart';
    case Metric = 'metric';
}
