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
            ->hasMigration('create_piped_tasks_tables')
            ->hasRoute('web');
    }
}
