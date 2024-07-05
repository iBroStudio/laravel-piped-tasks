<?php

namespace IBroStudio\PipedTasks\Commands;

use Symfony\Component\Console\Input\InputOption;

class MakeActionCommand extends BaseGeneratorCommand
{
    protected $name = 'make:piped-action';

    public $description = 'Generate an action for piped tasks';

    protected $type = 'Action';

    protected static string $stub = '/stubs/action.stub';

    protected function getOptions(): array
    {
        return [
            [
                'namespace',
                'N',
                InputOption::VALUE_REQUIRED,
                'The namespace to place the Action class.',
                'Processes\Payloads',
            ],
            [
                'package',
                'package',
                InputOption::VALUE_OPTIONAL,
                'Generate Action class in specified package.',
                null,
            ],
            [
                'vendor',
                'vendor',
                InputOption::VALUE_REQUIRED,
                'Directory for package option',
                'vendor',
            ],
            [
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Create the Action class even if the file already exists.',
            ],
        ];
    }
}
