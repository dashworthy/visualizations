<?php

namespace Dashworthy\Visualizations\DataGrids\Http\Requests;

use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        $action = $dataGrid->getInlineActions()->firstWhere('name', $this->input('action'));

        if ($action instanceof Action) {
            $rules['row_key'] = array_merge($rules['row_key'], $action->getRules());
        }

        return $rules;
    }
}
