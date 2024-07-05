<?php

namespace IBroStudio\PipedTasks\Commands;

use Symfony\Component\Console\Input\InputOption;

class MakePayloadInterfaceCommand extends BaseGeneratorCommand
{
    protected $name = 'make:piped-payload-interface';

    public $description = 'Generate a payload interface for piped tasks';

    protected $type = 'PayloadInterface';

    protected static string $stub = '/stubs/payload-interface.stub';

    protected function getOptions(): array
    {
        return [
            [
                'namespace',
                'N',
                InputOption::VALUE_REQUIRED,
                'The namespace to place the Payload interface.',
                'Processes\Payloads',
            ],
            [
                'package',
                'package',
                InputOption::VALUE_OPTIONAL,
                'Generate Payload interface in specified package.',
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
                'Create the Payload interface even if the file already exists.',
            ],
        ];
    }
}
