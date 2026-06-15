<?php

namespace Dashworthy\Visualizations\DataGrids\Enums;

enum ColumnPin: string
{
    case None = 'none';
    case Left = 'left';
    case Right = 'right';

    public function isPinned(): bool
    {
        return $this !== self::None;
    }

    public function isLeft(): bool
    {
        return $this === self::Left;
    }

    public function isRight(): bool
    {
        return $this === self::Right;
    }
}
