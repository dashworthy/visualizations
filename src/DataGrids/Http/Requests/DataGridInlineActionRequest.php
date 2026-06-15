<?php

namespace Dashworthy\Visualizations\DataGrids\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;

class DataGridInlineActionRequest extends FormRequest
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
        $route = $this->route();
        /** @var DataGrid|null $dataGrid */
        $dataGrid = $route ? $route->getController() : null;

        if (empty($dataGrid)) {
            abort(400, 'Could not find inline action');
        }

        /**
         * Grabs a list of available action names from the data grid.
         *
         * @var string[] $availableActionNames
         */
        $availableActionNames = $dataGrid->getInlineActions()->pluck('name')->toArray();

        $rules = [
            'action' => [
                'required',
                'string',
                Rule::in($availableActionNames),
            ],

            'row_key' => [
                'required',
            ],
        ];

        if ($dataGrid->resource) {
            $rules['row_key'][] = Rule::exists($dataGrid->resource, 'id');
        }

        return $rules;
    }
}
