<?php

use Dashworthy\Visualizations\DataGrids\Actions\Action;
use Dashworthy\Visualizations\DataGrids\Http\Requests\DataGridBulkActionRequest;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

test('constructor sets name and closure', function () {
    $closure = function (): void {};
    $action = new Action('test', $closure);

    expect($action->name)->toBe('test');
    expect($action->closure)->toBe($closure);
});

test('make sets name and closure', function () {
    $closure = function (): void {};
    $action = Action::make('test', $closure);

    expect($action->name)->toBe('test');
    expect($action->closure)->toBe($closure);
});

test('is authorized', function () {
    $closure = function (): void {};
    $action = new Action('test', $closure);
    $request = DataGridBulkActionRequest::create('/test', 'POST');

    expect($action->isAuthorized($request))->toBeTrue();

    Gate::shouldReceive('allows')->with('view')->andReturn(true);
    $action->withAuthorization('view');
    expect($action->isAuthorized($request))->toBeTrue();

    Gate::shouldReceive('allows')->with(['view', 'edit'])->andReturn(true);
    $action->withAuthorization(['view', 'edit']);
    expect($action->isAuthorized($request))->toBeTrue();

    $action->withAuthorization(fn ($req): true => true);
    expect($action->isAuthorized($request))->toBeTrue();
});

test('with authorization returns self', function () {
    $action = Action::make('Test', fn (): null => null);

    expect($action->withAuthorization('some-permission'))->toBe($action);
});

test('handle with empty rows returns empty array', function () {
    $action = new Action('test', function (): void {});

    expect($action->handle(new Collection))->toEqual([]);
});

test('handle with simple rows maps each', function () {
    $action = new Action('test', fn ($id) => $id);

    expect($action->handle(new Collection([1, 2, 3])))->toEqual([1, 2, 3]);
});

test('handle with single row returns array', function () {
    $action = new Action('test', fn ($id) => $id);

    expect($action->handle(new Collection([42])))->toEqual([42]);
});

test('handle with single row returning redirect response returns response', function () {
    $redirect = redirect('/dashboard');
    $action = new Action('test', fn ($id): RedirectResponse|\Illuminate\Routing\Redirector => $redirect);

    $result = $action->handle(new Collection([1]));

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    expect($result)->toBe($redirect);
});

test('default identity resolver processes raw values', function () {
    $action = Action::make('Process', fn ($id): int|float => $id * 2);

    expect($action->handle(collect([1, 2, 3])))->toEqual([2, 4, 6]);
});

test('resolve with model resolves a single row to a model', function () {
    DB::table('users')->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $receivedModel = null;
    $action = Action::make('Edit', function ($model) use (&$receivedModel): array {
        $receivedModel = $model;

        return ['edited' => true];
    })->resolveWithModel(User::class);

    $result = $action->handle(collect([1]));

    expect($receivedModel)->toBeInstanceOf(User::class);
    expect($receivedModel->name)->toBe('John Doe');
    expect($result)->toEqual([['edited' => true]]);
});

test('resolve with model single row not found returns empty', function () {
    $action = Action::make('Edit', fn ($model): array => ['edited' => true])
        ->resolveWithModel(User::class);

    expect($action->handle(collect([999])))->toEqual([]);
});

test('resolve with model resolves multiple rows to models', function () {
    DB::table('users')->insert([
        ['name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $names = [];
    $action = Action::make('Process', function ($model) use (&$names) {
        $names[] = $model->name;

        return $model->name;
    })->resolveWithModel(User::class);

    $result = $action->handle(collect([1, 2, 3]));

    expect($result)->toHaveCount(3);
    expect($names)->toContain('John Doe', 'Jane Doe', 'Bob Smith');
});

test('resolve with model resolves by a custom key column', function () {
    DB::table('users')->insert([
        'name' => 'Keyed User',
        'email' => 'keyed@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $receivedModel = null;
    $action = Action::make('Edit', function ($model) use (&$receivedModel): array {
        $receivedModel = $model;

        return ['ok' => true];
    })->resolveWithModel(User::class, 'email');

    $action->handle(collect(['keyed@example.com']));

    expect($receivedModel)->toBeInstanceOf(User::class);
    expect($receivedModel->email)->toBe('keyed@example.com');
});

test('resolve with model returns self', function () {
    $action = Action::make('Test', fn (): null => null);

    expect($action->resolveWithModel(User::class))->toBe($action);
});

test('resolve with model rejects a non-existent class', function () {
    $action = Action::make('Test', fn (): null => null);

    expect(fn () => $action->resolveWithModel('App\\Nope\\NotAClass'))
        ->toThrow(InvalidArgumentException::class);
});

test('resolve with model rejects an existing non-model class', function () {
    $action = Action::make('Test', fn (): null => null);

    expect(fn () => $action->resolveWithModel(stdClass::class))
        ->toThrow(InvalidArgumentException::class);
});

test('resolve with closure resolves a single row', function () {
    $action = Action::make('Edit', fn ($item) => $item)
        ->resolveWithClosure(fn (Collection $keys) => $keys->map(fn ($k) => "item-{$k}"));

    expect($action->handle(collect([5])))->toEqual(['item-5']);
});

test('resolve with closure single empty result returns empty', function () {
    $action = Action::make('Edit', fn ($item) => $item)
        ->resolveWithClosure(fn (Collection $keys) => collect());

    expect($action->handle(collect([5])))->toEqual([]);
});

test('resolve with closure resolves multiple rows', function () {
    $action = Action::make('Edit', fn ($item) => $item)
        ->resolveWithClosure(fn (Collection $keys) => $keys->map(fn ($k) => $k * 10));

    expect($action->handle(collect([1, 2, 3])))->toEqual([10, 20, 30]);
});

test('resolve with closure supports non-model items', function () {
    $action = Action::make('Edit', fn (array $item) => $item['label'])
        ->resolveWithClosure(fn (Collection $keys) => $keys->map(fn ($k) => ['label' => "L{$k}"]));

    expect($action->handle(collect([1, 2])))->toEqual(['L1', 'L2']);
});

test('resolve with closure returns self', function () {
    $action = Action::make('Test', fn (): null => null);

    expect($action->resolveWithClosure(fn (Collection $keys) => $keys))->toBe($action);
});

test('rules default to an empty array', function () {
    $action = Action::make('Edit', fn (): null => null);

    expect($action->getRules())->toBe([]);
});

test('rules stores and returns the assigned array', function () {
    $rules = ['required', Rule::exists('users', 'id')];
    $action = Action::make('Edit', fn (): null => null)->rules($rules);

    expect($action->getRules())->toBe($rules);
});

test('rules returns self', function () {
    $action = Action::make('Edit', fn (): null => null)->rules(['required']);

    expect($action->rules(['required']))->toBe($action);
});

test('to array returns name and empty meta', function () {
    $action = Action::make('Delete', fn (): null => null);
    $array = $action->toArray();

    expect($array['name'])->toBe('Delete');
    expect($array['meta'])->toEqual([]);
});

test('to array with meta includes meta values', function () {
    $action = Action::make('Delete', fn (): null => null);
    $action->meta('icon', 'trash');
    $action->meta('confirm', true);

    $array = $action->toArray();

    expect($array['name'])->toBe('Delete');
    expect($array['meta']['icon'])->toBe('trash');
    expect($array['meta']['confirm'])->toBeTrue();
});
