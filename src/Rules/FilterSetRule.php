<?php

namespace Dashworthy\Visualizations\Rules;

use Closure;
use Dashworthy\Visualizations\Contracts\FilterOperationContract;
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

            return;
        }

        // Once the set is well formed, every filter has an operator whose operation decides what its value may be
        $this->validator->after(fn (Validator $validator) => $this->validateFilterValues($validator, $attribute, $value));
    }

    /**
     * Checks each filter's value against the rules of the operation that binds it: the first in `visualizations.filters`
     * that can handle the filter's operator, as GenerateVisualizationQuery picks. A filter no operation handles gets no
     * value rules, and the query refuses it.
     *
     * @param  array{filters: array<int, array{filter_operator: string}>}  $filterSet
     */
    private function validateFilterValues(Validator $validator, string $attribute, array $filterSet): void
    {
        $filterOperations = collect(config('visualizations.filters'))
            ->map(fn (string $filterClass): FilterOperationContract => app($filterClass));
        $rules = [];

        foreach ($filterSet['filters'] as $index => $filter) {
            $filterOperator = FilterOperator::from($filter['filter_operator']);
            $filterOperation = $filterOperations->first(
                fn (FilterOperationContract $filterOperation): bool => $filterOperation->canHandle($filterOperator)
            );

            foreach ($filterOperation?->rules() ?? [] as $key => $keyRules) {
                $rules["{$attribute}.filters.{$index}.{$key}"] = $keyRules;
            }
        }

        $valueValidator = ValidatorFacade::make($validator->getData(), $rules, $validator->customMessages, $validator->customAttributes);

        foreach ($valueValidator->errors()->messages() as $failedAttribute => $messages) {
            foreach ($messages as $message) {
                $validator->errors()->add($failedAttribute, $message);
            }
        }
    }
}
