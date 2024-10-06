<?php

namespace IBroStudio\PipedTasks\Tests;

use IBroStudio\PipedTasks\PipedTasksServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'IBroStudio\\PipedTasks\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            PipedTasksServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/2024_10_05_142931_create_processes_table.php';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/2024_10_05_143615_create_processes_tasks_table.php';
        $migration->up();
    }
}
