<?php

namespace Dashworthy\Visualizations\DataGrids\Enums;

enum ColumnType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'datetime';
    case Boolean = 'boolean';
    case Point = 'point';
    case Chip = 'chip';
}
