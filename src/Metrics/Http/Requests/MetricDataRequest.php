<?php

namespace Dashworthy\Visualizations\Metrics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Dashworthy\Visualizations\Rules\FilterSetRule;

class MetricDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'filter_sets' => ['nullable', 'array'],
            'filter_sets.*' => [new FilterSetRule],
        ];
    }
}
