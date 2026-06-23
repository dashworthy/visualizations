<?php

namespace Dashworthy\Visualizations\DataGrids\Http\Requests;

use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Illuminate\Foundation\Http\FormRequest;

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

        $slug = $this->route('action');

        $action = $dataGrid->getInlineActions()->first(
            fn (Action $candidate): bool => $candidate->getSlug() === $slug
        );

        $rules = [
            'row_key' => ['required'],
        ];

        if ($action instanceof Action) {
            $rules['row_key'] = array_merge($rules['row_key'], $action->getRules());
        }

        return $rules;
    }
}
