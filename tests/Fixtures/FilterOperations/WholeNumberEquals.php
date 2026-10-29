<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\FilterOperations;

use Dashworthy\Visualizations\FilterOperations\Equality\Equals;

/**
 * An app's own equals operation, with rules of its own, configured ahead of the package's.
 */
class WholeNumberEquals extends Equals
{
    public function rules(): array
    {
        return [
            'value' => ['present', 'integer'],
        ];
    }
}
