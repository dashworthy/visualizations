<?php

namespace Dashworthy\Visualizations\Abstracts;

use Dashworthy\Visualizations\Contracts\VisualizationContract;
use Dashworthy\Visualizations\Data\FetchedData;
use Dashworthy\Visualizations\Data\VisualizationData;
use Dashworthy\Visualizations\Events\VisualizationQueryExecuted;
use Dashworthy\Visualizations\Query\GenerateVisualizationQuery;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * The machinery every kind of visualization shares: route naming, the schema
 * envelope, authorization, query generation and the query event.
 *
 * A kind fills in only what makes it that kind — its type, its class suffix,
 * its schema body, its primary visualizables and how it fetches rows. It also
 * declares `handleData()` and `handleSchema()` as one-line calls to
 * `respondWithData()` and `respondWithSchema()`, because each takes the kind's
 * own form request and Laravel validates that request by its type-hint.
 */
abstract class Visualization implements VisualizationContract
{
    final public function __construct() {}

    /**
     * The suffix stripped from the class name when deriving route names and
     * paths, e.g. 'Chart' turns `RevenueChart` into `revenues`.
     */
    abstract protected function getClassSuffix(): string;

    /**
     * The kind-specific part of the schema, placed between the visualization
     * key and the floating filters.
     *
     * @return array<string, mixed>
     */
    abstract protected function getSchemaBody(): array;

    /**
     * The visualizables that make up the kind's output, before floating filters.
     *
     * @return Collection<int, covariant Visualizable>
     */
    abstract protected function getPrimaryVisualizables(): Collection;

    /**
     * Runs the generated query and shapes the response payload.
     */
    abstract protected function fetchData(Builder $query, FormRequest $request): FetchedData;

    /**
     * Builds the definitional structure of the visualization for front-end consumption.
     *
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $visualization = new static;

        return [
            'visualization_key' => $visualization->getVisualizationKey(),
            ...$visualization->getSchemaBody(),
            'floating_filters' => $visualization->getFloatingFilters()->map->toArray(),
        ];
    }

    /**
     * Defines optional floating filters. Floating filters allow filtering on fields that are not part of the
     * visualization's visible data, such as filtering by a related entity.
     *
     * @return Collection<int, FloatingFilter>
     */
    public function getFloatingFilters(): Collection
    {
        return collect();
    }

    /**
     * Assembles all visualizables (primary visualizables, then floating filters) into a single collection for
     * query generation. Uses concat() rather than merge() to guarantee no items are dropped regardless of
     * collection key types.
     *
     * @return Collection<int, Visualizable>
     */
    public function getVisualizables(): Collection
    {
        /** @var Collection<int, Visualizable> $visualizables */
        $visualizables = $this->getPrimaryVisualizables()->concat($this->getFloatingFilters());

        return $visualizables;
    }

    /**
     * Automatically generates the route name used as Laravel's named route.
     */
    public function getRouteName(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before($this->getClassSuffix())
            ->snake('-')
            ->plural()
            ->prepend($this->getRoutePrefix().'.')
            ->toString();
    }

    /**
     * Automatically generates the route path used as the URL path.
     */
    public function getRoutePath(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->before($this->getClassSuffix())
            ->plural()
            ->snake('-')
            ->prepend('/')
            ->prepend($this->getRoutePrefix())
            ->toString();
    }

    public function getVisualizationKey(): string
    {
        return $this->getRouteName();
    }

    /**
     * Parses the request's filters and sorts. A kind that accepts less input overrides this.
     */
    protected function getVisualizationData(FormRequest $request): VisualizationData
    {
        return VisualizationData::fromRequest($request);
    }

    /**
     * Authorizes, generates and runs the query, then reports it through `VisualizationQueryExecuted`.
     *
     * @throws Exception
     */
    protected function respondWithData(FormRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        $this->authorizeVisualization();

        $query = GenerateVisualizationQuery::make()->handle(
            $this->getQuery(),
            $this->getVisualizables(),
            $this->getVisualizationData($request)
        );

        $sql = $query->toRawSql();
        $fetched = $this->fetchData($query, $request);

        event(new VisualizationQueryExecuted(
            visualizationKey: $this->getVisualizationKey(),
            visualizationType: $this->getVisualizationType()->getTypeKey(),
            sql: $sql,
            durationMs: (microtime(true) - $startedAt) * 1000,
            rowCount: $fetched->rowCount,
        ));

        return response()->json($fetched->payload);
    }

    /**
     * Authorizes, then returns the schema as JSON.
     */
    protected function respondWithSchema(): JsonResponse
    {
        $this->authorizeVisualization();

        return response()->json(static::schema());
    }

    private function authorizeVisualization(): void
    {
        if (method_exists($this, 'getPermissionName')) {
            Gate::authorize($this->getPermissionName());
        }
    }
}
