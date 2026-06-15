<?php

namespace Dashworthy\Visualizations\Charts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChartSchemaRequest extends FormRequest
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
