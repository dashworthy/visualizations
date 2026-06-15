<?php

namespace Dashworthy\Visualizations\Rules;

use Dashworthy\Visualizations\Enums\SortOperator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SortRule implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $validator = ValidatorFacade::make(
            $value,
            [
                'field' => ['required', 'string', 'min:1'],
                'sort_operator' => ['required', Rule::enum(SortOperator::class)],
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
