<?php

namespace Dashworthy\Visualizations\DataGrids\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Traits\Macroable;
use Dashworthy\Visualizations\DataGrids\Abstracts\DataGrid;
use Dashworthy\Visualizations\Traits\HandlesMetaData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Action
 *
 * This class handles actions on individual rows or collections of rows within a data grid.
 */
class Action
{
    use HandlesMetaData, Macroable;

    /**
     * @var array<string, mixed>
     */
    protected array $meta = [];

    /**
     * The method to authorize the action if any.
     *
     * @var Closure|array<int|string, mixed>|string|null
     */
    protected Closure|array|string|null $authorize = null;

    /**
     * Action constructor.
     *
     * @param  string  $name  The name of the action.
     * @param  Closure  $closure  The closure to be executed for each row.
     */
    public function __construct(public string $name, public Closure $closure) {}

    public static function make(string $name, Closure $closure): self
    {
        return new self($name, $closure);
    }

    /**
     * Sets the authorization method for the action.
     *
     * @param  Closure|array<int|string, mixed>|string  $authorize
     * @return $this
     */
    public function withAuthorization(Closure|array|string $authorize): self
    {
        $this->authorize = $authorize;

        return $this;
    }

    /**
     * Checks if the action is authorized.
     *
     * @param  Request  $request  The current request instance.
     * @return bool True if authorized, false otherwise.
     */
    public function isAuthorized(Request $request): bool
    {
        return match (true) {
            is_string($this->authorize),
            is_array($this->authorize) => Gate::allows($this->authorize),
            is_null($this->authorize) => true,
            default => ($this->authorize)($request),
        };
    }

    /**
     * Checks if the data grid has a valid resource model.
     *
     * @param  DataGrid  $dataGrid  The data grid instance.
     * @return bool True if the data grid has a valid resource model, false otherwise.
     */
    protected function hasResource(DataGrid $dataGrid): bool
    {
        return ! in_array($dataGrid->resource, [null, '', '0'], true)
            && class_exists($dataGrid->resource)
            && is_subclass_of($dataGrid->resource, Model::class);
    }

    /**
     * Handles a single row or a collection of rows.
     *
     * When exactly one row key is supplied, processing goes through processSingleRow,
     * which is the only code path that may return a RedirectResponse.
     *
     * @param  DataGrid  $dataGrid  The data grid instance.
     * @param  Collection<int|string, mixed>  $rows  The collection of row IDs to be processed.
     * @return array<int|string, mixed>|Response
     */
    public function handle(DataGrid $dataGrid, Collection $rows): array|Response
    {
        if ($rows->isEmpty()) {
            return [];
        }

        if ($rows->count() === 1) {
            return $this->processSingleRow($dataGrid, $rows->first());
        }

        return $this->hasResource($dataGrid)
            ? $this->processModelRows($dataGrid, $rows)
            : $this->processSimpleRows($rows);
    }

    /**
     * Processes exactly one row key. This is the only code path that may return a
     * RedirectResponse — when the closure itself returns one.
     *
     * @param  DataGrid  $dataGrid  The data grid instance.
     * @param  string|int  $rowKey  The single row key to process.
     * @return array<int, mixed>|Response
     */
    private function processSingleRow(DataGrid $dataGrid, string|int $rowKey): array|Response
    {
        if ($this->hasResource($dataGrid)) {
            /** @var Model $model */
            $model = new $dataGrid->resource;
            $record = $model::query()->find($rowKey);

            if ($record === null) {
                return [];
            }

            $result = ($this->closure)($record);
        } else {
            $result = ($this->closure)($rowKey);
        }

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return [$result];
    }

    /**
     * Processes a collection of model rows using the closure provided.
     *
     * @param  DataGrid  $dataGrid  The data grid instance.
     * @param  Collection<int|string, mixed>  $rows  The collection of row IDs to be processed.
     * @return array<int, mixed>
     */
    private function processModelRows(DataGrid $dataGrid, Collection $rows): array
    {
        $result = [];
        /** @var Model $model */
        $model = new $dataGrid->resource;

        $model::query()
            ->whereIn('id', $rows)
            ->eachById(function (Model $model) use (&$result): void {
                $result[] = ($this->closure)($model);
            });

        return $result;
    }

    /**
     * Processes a collection of simple rows using the closure provided.
     *
     * @param  Collection<int|string, mixed>  $rows  The collection of row IDs to be processed.
     * @return array<int, mixed>
     */
    private function processSimpleRows(Collection $rows): array
    {
        $result = [];
        foreach ($rows as $rowId) {
            $result[] = ($this->closure)($rowId);
        }

        return $result;
    }

    /**
     * Used to provide an array representation of the row action to be used in the data grid schema.
     *
     * @return array{
     *     name: string,
     *     meta: array<string, mixed>
     * } The array representation of the row action.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'meta' => $this->meta,
        ];
    }
}
