<?php

namespace Dashworthy\Visualizations\DataGrids\Http\Requests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;

class DataGridBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BindingResolutionException
     */
    public function rules(): array
    {
        $route = $this->route();
        /** @var DataGrid|null $dataGrid */
        $dataGrid = $route ? $route->getController() : null;

        if (empty($dataGrid)) {
            abort(400, 'Could not find bulk action');
        }

        /**
         * Grabs a list of available action names from the data grid.
         *
         * @var string[] $availableActionNames
         */
        $availableActionNames = $dataGrid->getBulkActions()->pluck('name')->toArray();

        $rules = [
            'action' => [
                'required',
                'string',
                Rule::in($availableActionNames),
            ],

            'row_keys' => [
                'required',
                'array',
                'min:1',
            ],
            'row_keys.*' => [
                'required',
            ],
        ];

        /**
         * If the data grid has a resource, we can validate the row keys against the resource.
         */
        if ($dataGrid->resource) {
            $rules['row_keys.*'][] = Rule::exists($dataGrid->resource, 'id');
        }

        return $rules;
    }
}
