<?php

namespace Dashworthy\Visualizations\Contracts;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Dashworthy\Visualizations\Abstracts\FloatingFilter;
use Dashworthy\Visualizations\Abstracts\Visualizable;

interface VisualizationContract
{
    /**
     * A unique string identifier for this visualization, used as the schema key sent to the front-end
     * and as the basis for keying saved state (e.g. DataGrid views).
     */
    public function getVisualizationKey(): string;

    /**
     * The route group prefix. Used to namespace generated route names and paths.
     * Example: returning 'grids' produces route names like 'grids.users'.
     */
    public function getRoutePrefix(): string;

    /**
     * The Laravel named route for this visualization, auto-generated from the class name and route prefix.
     */
    public function getRouteName(): string;

    /**
     * The URL path for this visualization's routes, auto-generated from the class name and route prefix.
     */
    public function getRoutePath(): string;

    /**
     * The base query that visualization data is built from.
     * Filters, sorts, and pagination are applied on top of this query at request time.
     */
    public function getQuery(): Builder;

    /**
     * Optional floating filters — fields not part of the primary visualization output (columns/datasets)
     * that can still be used to filter the underlying query.
     *
     * @return Collection<int, FloatingFilter>
     */
    public function getFloatingFilters(): Collection;

    /**
     * All visualizables for this visualization — the primary data fields plus any floating filters —
     * assembled into a single collection for use by GenerateVisualizationQuery.
     *
     * @return Collection<int, Visualizable>
     */
    public function getVisualizables(): Collection;
}
