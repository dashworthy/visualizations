<?php

namespace Dashworthy\Visualizations\DataGrids\Actions;

use Closure;
use Dashworthy\Visualizations\Traits\HandlesMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\Macroable;
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
     * Resolves the selected row keys into the items passed to the action closure.
     * Defaults (in the constructor) to identity: raw row keys pass through unchanged.
     * Returns an Enumerable so resolvers may stream (LazyCollection) or eager-load (Collection).
     *
     * @var Closure(Collection<int, int|string>): Enumerable<int, mixed>
     */
    protected Closure $resolver;

    /**
     * Validation rules applied to each selected row key during request validation.
     * Empty by default (no row-key constraints beyond the base `required`).
     *
     * @var array<int, mixed>
     */
    protected array $rules = [];

    /**
     * Action constructor.
     *
     * @param  string  $name  The name of the action.
     * @param  Closure  $closure  The closure to be executed for each row.
     */
    public function __construct(public string $name, public Closure $closure)
    {
        $this->resolveWithClosure(static fn (Collection $keys): Collection => $keys);
    }

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
     * Resolve selected rows with custom logic. The closure receives the collection
     * of row keys and must return an Enumerable of items; each item is passed to
     * the action closure. This is the single assignment point for the resolver.
     *
     * @param  Closure(Collection<int, int|string>): Enumerable<int, mixed>  $resolver
     * @return $this
     */
    public function resolveWithClosure(Closure $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Set the validation rules applied to each selected row key during request
     * validation (merged on top of the base `required`).
     *
     * @param  array<int, mixed>  $rules
     * @return $this
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Resolve selected rows to Eloquent models, keyed by $key (default the primary
     * key). Sugar over resolveWithClosure() installing the standard chunked resolver.
     *
     * @param  class-string<Model>  $model
     * @param  string  $key  Column to resolve rows by (default 'id').
     * @return $this
     *
     * @throws \InvalidArgumentException when $model is not an existing Model subclass
     */
    public function resolveWithModel(string $model, string $key = 'id'): self
    {
        if (! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            throw new \InvalidArgumentException(
                "Action model [{$model}] must be an existing ".Model::class.' subclass.'
            );
        }

        return $this->resolveWithClosure(
            // Filters by $key but lazyById() cursors by the model's primary key for chunked iteration.
            fn (Collection $keys): LazyCollection => $model::query()
                ->whereIn($key, $keys)
                ->lazyById()
        );
    }

    /**
     * Handles a single row or a collection of rows.
     *
     * When exactly one row key is supplied, processing goes through processSingleRow,
     * which is the only code path that may return a RedirectResponse.
     *
     * @param  Collection<int, int|string>  $rows
     * @return array<int|string, mixed>|Response
     */
    public function handle(Collection $rows): array|Response
    {
        if ($rows->isEmpty()) {
            return [];
        }

        if ($rows->count() === 1) {
            return $this->processSingleRow($rows->first());
        }

        return $this->processRows($rows);
    }

    /**
     * Processes exactly one row key. The only code path that may return a
     * RedirectResponse — when the closure itself returns one.
     *
     * @return array<int, mixed>|Response
     */
    private function processSingleRow(string|int $rowKey): array|Response
    {
        $item = ($this->resolver)(collect([$rowKey]))->first();

        if ($item === null) {
            return [];
        }

        $result = ($this->closure)($item);

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        return [$result];
    }

    /**
     * Processes a collection of rows by running the resolver and applying the
     * closure to each resolved item.
     *
     * @param  Collection<int, int|string>  $rows
     * @return array<int, mixed>
     */
    private function processRows(Collection $rows): array
    {
        $result = [];

        foreach (($this->resolver)($rows) as $item) {
            $result[] = ($this->closure)($item);
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
