<?php

namespace Dashworthy\Visualizations\Rules;

use Closure;
use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\Enums\FilterSetOperator;
use Dashworthy\Visualizations\FilterOperations\FilterOperationResolver;
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

            return;
        }

        $this->validateFilterValues($attribute, $value);
    }

    /**
     * Checks each filter's value against the rules of the operation its operator resolves to, so a value reaches only
     * an operation that can bind it. The rules run over the request's own data under their full path, so every message,
     * an object rule's included, reads as one of the request's own.
     */
    private function validateFilterValues(string $attribute, mixed $filterSet): void
    {
        $rules = $this->filterValueRules($attribute, $filterSet);

        if ($rules === []) {
            return;
        }

        $validator = ValidatorFacade::make(
            $this->validator->getData(),
            $rules,
            $this->validator->customMessages,
            $this->validator->customAttributes,
        );

        foreach ($validator->errors()->messages() as $failedAttribute => $messages) {
            foreach ($messages as $message) {
                $this->validator->errors()->add($failedAttribute, $message);
            }
        }
    }

    /**
     * Each filter's value rules, keyed to the filter's full path. A filter whose operator resolves to no operation gets
     * none, and the query refuses it.
     *
     * @return array<string, array<int, mixed>>
     */
    private function filterValueRules(string $attribute, mixed $filterSet): array
    {
        // The structure has passed, so every filter is an array with a known operator
        $filters = $filterSet['filters'];
        $filterOperationResolver = FilterOperationResolver::make();
        $rules = [];

        foreach ($filters as $index => $filter) {
            $filterOperator = FilterOperator::from($filter['filter_operator']);

            foreach ($filterOperationResolver->resolve($filterOperator)?->rules() ?? [] as $key => $keyRules) {
                $rules["{$attribute}.filters.{$index}.{$key}"] = $keyRules;
            }
        }

        return $rules;
    }
}
