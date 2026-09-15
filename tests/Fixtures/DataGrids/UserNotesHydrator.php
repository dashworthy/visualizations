<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Dashworthy\Visualizations\Contracts\HydratorContract;
use Dashworthy\Visualizations\DataGrids\Enums\ColumnType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolves every note for a page of users in one query.
 *
 * The to-many case the feature exists for: joining notes to the grid query would return one row per
 * note, so the grid would stop being one row per user.
 */
class UserNotesHydrator implements HydratorContract
{
    public function keyedBy(): string
    {
        return 'ID';
    }

    public function columnType(): ColumnType|string
    {
        return ColumnType::Text;
    }

    public function resolve(Collection $keys): array
    {
        return DB::table('notes')
            ->whereIn('user_id', $keys)
            ->orderBy('id')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $notes): string => $notes->pluck('body')->implode('; '))
            ->all();
    }
}
