<?php

namespace Dashworthy\Visualizations\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Passes a single value: a string, number or boolean. Pair it with `nullable` to allow null.
 */
class ScalarRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value)) {
            $fail('The :attribute must be a single value.');
        }
    }
}
