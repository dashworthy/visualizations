<?php

namespace Dashworthy\Visualizations\DataGrids\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataGridSchemaRequest extends FormRequest
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
