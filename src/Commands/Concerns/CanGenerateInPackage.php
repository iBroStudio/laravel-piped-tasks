<?php

namespace IBroStudio\PipedTasks\Commands\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait CanGenerateInPackage
{
    protected function packageNamespace(): string
    {
        $composer = Str::of($this->getRootPath())
            ->before('/src')
            ->append('/composer.json');

        if (! file_exists($composer)) {
            throw new \RuntimeException("composer.json not found for {$this->option('package')}");
        }

        $package = File::json($composer);

        return trim(array_search('src/', $package['autoload']['psr-4']), '\\');
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->getRootPath().str_replace('\\', '/', $name).'.php';
    }

    protected function getRootPath()
    {
        if (! is_null($this->option('package'))) {
            return Str::of($this->option('package'))
                ->append('/src');
        }

        return $this->laravel['path'].'/';
    }

    protected function rootNamespace(): string
    {
        if (! is_null($this->option('package'))) {
            return $this->packageNamespace();
        }

        return $this->laravel->getNamespace();
    }
}
