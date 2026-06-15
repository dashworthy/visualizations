<?php

namespace Dashworthy\Visualizations\Rules;

use Closure;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FilterSetRule implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validator = ValidatorFacade::make(
            $value,
            [
                'filter_set_operator' => ['required', Rule::enum(FilterSetOperator::class)],
                'filters' => ['required', 'array'],
                'filters.*.field' => ['required', 'string', 'min:1'],
                'filters.*.value' => ['present'],
                'filters.*.filter_operator' => ['required', Rule::enum(FilterOperator::class)],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->failed() as $failedAttribute => $rules) {
                foreach ($rules as $rule => $params) {
                    $this->validator->addFailure($attribute.'.'.$failedAttribute, $rule, $params);
                }
            }
        }
    }
}
