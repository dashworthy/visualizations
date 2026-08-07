<?php

namespace Dashworthy\Visualizations\Enums;

use Dashworthy\Visualizations\Contracts\DefinesVisualizationType;

/**
 * The kinds of visualization this package defines.
 *
 * Not the whole set — `DefinesVisualizationType` is the type, and this enum is
 * only what ships here. An application adding its own kind implements that
 * contract on its own enum rather than waiting for a case to be added here,
 * the same way `DashboardSection` works in `dashworthy/dashboards`.
 *
 * The values are the wire format: they are what a consumer persists or sends
 * to a front-end, so renaming one is a breaking change.
 */
enum VisualizationType: string implements DefinesVisualizationType
{
    case DataGrid = 'datagrid';
    case Chart = 'chart';
    case Metric = 'metric';

    public function getTypeKey(): string
    {
        return $this->value;
    }
}
