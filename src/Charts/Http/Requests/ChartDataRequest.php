<?php

namespace Dashworthy\Visualizations\Charts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Dashworthy\Visualizations\Rules\FilterSetRule;
use Dashworthy\Visualizations\Rules\SortRule;

class ChartDataRequest extends FormRequest
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
            'sorts' => ['nullable', 'array'],
            'sorts.*' => [new SortRule],

            'filter_sets' => ['nullable', 'array'],
            'filter_sets.*' => [new FilterSetRule],
        ];
    }
}
