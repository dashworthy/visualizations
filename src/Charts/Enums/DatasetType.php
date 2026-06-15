<?php

namespace Dashworthy\Visualizations\Charts\Enums;

enum DatasetType: string
{
    case Line = 'line';
    case Bar = 'bar';
    case Pie = 'pie';
    case Doughnut = 'doughnut';
    case Scatter = 'scatter';
    case Bubble = 'bubble';
    case Area = 'area';
    case Radar = 'radar';
}
