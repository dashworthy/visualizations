<?php

use App\Hydrators\CompilableHydrator;
use Dashworthy\Visualizations\Contracts\HydratorContract;
use Illuminate\Support\Facades\File;

/**
 * GeneratorCommand returns false when the file already exists, and (int) false is 0 — so
 * assertExitCode(0) cannot tell "wrote it" from "refused, it is already there". Clearing the
 * directory around every test is what makes the exit code mean something.
 */
function clearGeneratedHydrators(): void
{
    if (is_dir(app_path('Hydrators'))) {
        File::deleteDirectory(app_path('Hydrators'));
    }
}

beforeEach(fn () => clearGeneratedHydrators());
afterEach(fn () => clearGeneratedHydrators());

test('make hydrator command creates file', function () {
    $expectedPath = app_path('Hydrators/TestHydrator.php');
    expect($expectedPath)->not->toBeFile();

    $this->artisan('make:hydrator', ['name' => 'TestHydrator'])
        ->assertExitCode(0);

    expect($expectedPath)->toBeFile();

    $content = file_get_contents($expectedPath);
    expect($content)->toContain('class TestHydrator implements HydratorContract');
    expect($content)->toContain('namespace App\Hydrators;');
    expect($content)->toContain('use Dashworthy\Visualizations\Contracts\HydratorContract;');
    expect($content)->toContain('keyedBy()');
    expect($content)->toContain('columnType()');
    expect($content)->toContain('resolve(Collection $keys)');
});

test('make hydrator command replaces name placeholder', function () {
    $expectedPath = app_path('Hydrators/UserNotesHydrator.php');
    expect($expectedPath)->not->toBeFile();

    $this->artisan('make:hydrator', ['name' => 'UserNotesHydrator'])
        ->assertExitCode(0);

    $content = file_get_contents($expectedPath);
    expect($content)->not->toContain('HYDRATOR_NAME');
    expect($content)->toContain('class UserNotesHydrator implements HydratorContract');
});

test('the generated hydrator is valid php that satisfies the contract', function () {
    // The sibling make: command tests only grep the stub for strings, so one that cannot compile —
    // or whose signatures drift from the contract — would pass them.
    $path = app_path('Hydrators/CompilableHydrator.php');
    expect($path)->not->toBeFile();

    $this->artisan('make:hydrator', ['name' => 'CompilableHydrator'])->assertExitCode(0);

    exec('php -l '.escapeshellarg($path).' 2>&1', $output, $exitCode);
    expect($exitCode)->toBe(0, implode("\n", $output));

    require_once $path;

    $hydrator = new CompilableHydrator;

    expect($hydrator)->toBeInstanceOf(HydratorContract::class)
        ->and($hydrator->keyedBy())->toBeString()
        ->and($hydrator->resolve(collect([1, 2])))->toBeArray();
});
