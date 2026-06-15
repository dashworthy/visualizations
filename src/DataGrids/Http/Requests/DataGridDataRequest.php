<?php

namespace Dashworthy\Visualizations\DataGrids\Http\Requests;

use Dashworthy\Visualizations\Rules\FilterSetRule;
use Dashworthy\Visualizations\Rules\SortRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DataGridDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Standard pagination
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'page' => ['sometimes', 'integer', 'min:1'],

            'first' => ['sometimes', 'integer', 'min:0'],
            'last' => ['sometimes', 'integer', 'min:0'],

            'sorts' => ['nullable', 'array'],
            'sorts.*' => [new SortRule],

            'filter_sets' => ['nullable', 'array'],
            'filter_sets.*' => [new FilterSetRule],
        ];
    }

    /**
     * Post validation
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $first = $this->input('first');
            $last = $this->input('last');
            if ($first !== null && $last !== null) {
                if (abs($first - $last) > 1000) {
                    $validator->errors()->add('first', 'The gap between first and last must not exceed 1000.');
                    $validator->errors()->add('last', 'The gap between first and last must not exceed 1000.');
                }
                if ($first >= $last) {
                    $validator->errors()->add('first', 'First must be less than last.');
                    $validator->errors()->add('last', 'Last must be greater than first.');
                }
            }
        });
    }
}
