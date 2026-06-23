<?php

namespace Dashworthy\Visualizations\DataGrids\Http\Requests;

use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        $action = $dataGrid->getBulkActions()->firstWhere('name', $this->input('action'));

        if ($action instanceof Action) {
            $rules['row_keys.*'] = array_merge($rules['row_keys.*'], $action->getRules());
        }

        return $rules;
    }
}
