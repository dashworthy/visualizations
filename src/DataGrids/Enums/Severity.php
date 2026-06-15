<?php

namespace Dashworthy\Visualizations\DataGrids\Enums;

enum Severity: string
{
    case SUCCESS = 'success';
    case INFO = 'info';
    case WARNING = 'warning';
    case DANGER = 'danger';
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case CONTRAST = 'contrast';
}
