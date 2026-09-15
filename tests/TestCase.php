<?php

namespace Dashworthy\Visualizations\Tests;

use Dashworthy\Visualizations\VisualizationsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });
        }

        // A second table no grid query joins to, so a hydrator has something real to resolve from.
        if (! Schema::hasTable('notes')) {
            Schema::create('notes', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('body');
            });
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            VisualizationsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
