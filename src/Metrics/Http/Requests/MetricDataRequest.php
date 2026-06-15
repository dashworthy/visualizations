<?php

namespace Dashworthy\Visualizations\Metrics\Http\Requests;

use Dashworthy\Visualizations\Rules\FilterSetRule;
use Illuminate\Foundation\Http\FormRequest;

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
