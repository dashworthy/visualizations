<?php

use Dashworthy\Visualizations\Enums\FilterOperator;
use Dashworthy\Visualizations\FilterOperations\Equality\Equals;
use Dashworthy\Visualizations\FilterOperations\FilterOperationResolver;
use Dashworthy\Visualizations\FilterOperations\Sets\In;
use Dashworthy\Visualizations\Tests\Fixtures\FilterOperations\WholeNumberEquals;

it('resolves the configured operation that handles the operator', function () {
    expect(FilterOperationResolver::make()->resolve(FilterOperator::IN))->toBeInstanceOf(In::class);
});

it('resolves the first configured operation that handles the operator', function () {
    config()->set('visualizations.filters', [WholeNumberEquals::class, Equals::class]);

    expect(FilterOperationResolver::make()->resolve(FilterOperator::EQUALS))->toBeInstanceOf(WholeNumberEquals::class);
});

it('resolves nothing when no configured operation handles the operator', function () {
    config()->set('visualizations.filters', [Equals::class]);

    expect(FilterOperationResolver::make()->resolve(FilterOperator::IN))->toBeNull();
});

it('resolves the configured operations once per resolver', function () {
    $resolved = 0;
    app()->bind(Equals::class, function () use (&$resolved) {
        $resolved++;

        return new Equals;
    });
    config()->set('visualizations.filters', [Equals::class]);

    $resolver = FilterOperationResolver::make();
    $resolver->resolve(FilterOperator::EQUALS);
    $resolver->resolve(FilterOperator::IN);

    expect($resolved)->toBe(1);
});
