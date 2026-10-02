<?php

use Dashworthy\Visualizations\Charts\Datasets\Bar;
use Dashworthy\Visualizations\Charts\Labels\Label;
use Dashworthy\Visualizations\FloatingFilters\DateRange;
use Dashworthy\Visualizations\Metrics\Value;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\CountingHydrator;
use Dashworthy\Visualizations\Tests\Fixtures\DataGrids\StaticHydrator;

test('keeps an ordinary expression as SQL', function (string $expression) {
    expect(Label::make($expression, 'Field')->getSelectWith())->toBe($expression);
})->with([
    'qualified column' => 'users.id',
    'aggregate' => 'count(*)',
    'bare column' => 'name',
    'multi-word expression' => "CASE WHEN active THEN 'y' ELSE 'n' END",
]);

test('treats a string naming no hydrator class as SQL, even if it looks like one', function () {
    // A typo'd class-string cannot be told from SQL, so it fails at query time instead.
    expect(Label::make('App\Hydrators\NotesHydratr', 'Notes')->getSelectWith())
        ->toBe('App\Hydrators\NotesHydratr');
});

test('still binds the bindings given with SQL', function () {
    $label = Label::make('coalesce(name, ?)', 'Field', ['n']);

    expect($label->getSelectWith())->toBe('coalesce(name, ?)')
        ->and($label->getSelectWithBindings())->toBe(['n']);
});

test('refuses a hydrator instance on anything but a column', function (string $class) {
    expect(fn () => $class::make(new StaticHydrator, 'Field'))
        ->toThrow(LogicException::class, "{$class} cannot be hydrated");
})->with([Label::class, Bar::class, DateRange::class, Value::class]);

test('refuses a hydrator class-string on anything but a column, without constructing it', function (string $class) {
    CountingHydrator::$constructed = 0;

    expect(fn () => $class::make(CountingHydrator::class, 'Field'))
        ->toThrow(LogicException::class, "{$class} cannot be hydrated")
        ->and(CountingHydrator::$constructed)->toBe(0);
})->with([Label::class, Bar::class, DateRange::class, Value::class]);

test('refuses bindings given with a hydrator', function () {
    // Checked before the hook, so the message names the real mistake on every visualizable.
    expect(fn () => Label::make(new StaticHydrator, 'Field', ['x']))
        ->toThrow(InvalidArgumentException::class, 'bindings');
});
