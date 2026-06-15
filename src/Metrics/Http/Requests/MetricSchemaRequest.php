<?php

namespace Dashworthy\Visualizations\Metrics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MetricSchemaRequest extends FormRequest
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
        return [];
    }
}
