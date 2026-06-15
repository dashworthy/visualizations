<?php

namespace Dashworthy\Visualizations\Charts\Http\Requests;

use Dashworthy\Visualizations\Rules\FilterSetRule;
use Dashworthy\Visualizations\Rules\SortRule;
use Illuminate\Foundation\Http\FormRequest;

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
