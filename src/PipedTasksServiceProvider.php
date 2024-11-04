<?php

namespace IBroStudio\PipedTasks;

use IBroStudio\PipedTasks\Commands\MakeActionCommand;
use IBroStudio\PipedTasks\Commands\MakePayloadCommand;
use IBroStudio\PipedTasks\Commands\MakePayloadInterfaceCommand;
use IBroStudio\PipedTasks\Commands\MakeProcessCommand;
use IBroStudio\PipedTasks\Commands\MakeTaskCommand;
use IBroStudio\PipedTasks\Commands\PipedTasksInstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PipedTasksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-piped-tasks')
            ->hasConfigFile()
            ->hasCommands(
                PipedTasksInstallCommand::class,
                MakeActionCommand::class,
                MakePayloadCommand::class,
                MakePayloadInterfaceCommand::class,
                MakeProcessCommand::class,
                MakeTaskCommand::class,
            )
            ->hasMigration('2024_10_05_142931_create_piped_tasks_tables')
            ->hasRoute('web')
            ->runsMigrations();
    }

    public function bootingPackage()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
