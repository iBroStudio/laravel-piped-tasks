<?php

namespace IBroStudio\PipedTasks\Commands;

use IBroStudio\PipedTasks\Commands\Concerns\CanGenerateInPackage;
use IBroStudio\PipedTasks\Commands\Concerns\CanGenerateInSubFolders;
use Illuminate\Console\GeneratorCommand;

abstract class BaseGeneratorCommand extends GeneratorCommand
{
    use CanGenerateInPackage;
    use CanGenerateInSubFolders;

    protected static string $stub;

    protected function getStub(): string
    {
        return $this->resolveStubPath(static::$stub);
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../..'.$stub;
    }
}
