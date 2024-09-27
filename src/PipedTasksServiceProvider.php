<?php

namespace IBroStudio\PipedTasks;

use IBroStudio\PipedTasks\Commands\MakeActionCommand;
use IBroStudio\PipedTasks\Commands\MakePayloadCommand;
use IBroStudio\PipedTasks\Commands\MakePayloadInterfaceCommand;
use IBroStudio\PipedTasks\Commands\MakeProcessCommand;
use IBroStudio\PipedTasks\Commands\MakeTaskCommand;
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
                MakeActionCommand::class,
                MakePayloadCommand::class,
                MakePayloadInterfaceCommand::class,
                MakeProcessCommand::class,
                MakeTaskCommand::class
            );
    }
}
