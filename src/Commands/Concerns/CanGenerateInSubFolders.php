<?php

namespace IBroStudio\PipedTasks\Commands\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

trait CanGenerateInSubFolders
{
    protected function qualifyClass($name): string
    {
        $sub = Str::of($this->getNameInput())->split('/\//');

        $name = Str::of($this->getNameInput())
            ->when($sub->count() > 1, function (Stringable $string) use ($sub) {
                return $sub->last();
            });

        return $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')).'\\'.$name;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $sub = Str::of($this->getNameInput())->split('/\//');

        $namespace = Str::of(trim($this->option('namespace'), '\\'))
            ->when($sub->count() > 1, function (Stringable $string) {
                return $string->append('\\')
                    ->append(str_replace('/', '\\', Str::beforeLast($this->getNameInput(), '/')));
            });

        return trim($rootNamespace.'\\'.$namespace, '\\');
    }
}
