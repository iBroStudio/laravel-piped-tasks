<?php

namespace IBroStudio\PipedTasks\Commands;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class MakePayloadCommand extends BaseGeneratorCommand
{
    protected $name = 'make:piped-payload';

    public $description = 'Generate a process payload for piped tasks';

    protected $type = 'Payload';

    protected static string $stub = '/stubs/payload.stub';

    protected string $payloadInterface;

    protected string $payloadInterfaceNamespace;

    public function handle(): bool
    {
        $this->payloadInterface =
            Str::of($this->getNameInput())
                ->split('/(?=[A-Z])/')
                ->take(-2)
                ->join('');

        if ($this->payloadInterface === $this->getNameInput()) {
            $this->payloadInterface .= 'Contract';
        }

        $this->payloadInterfaceNamespace =
            Str::of($this->getDefaultNamespace($this->rootNamespace()))
                ->append('\Contracts');

        parent::handle();

        $this->call('make:piped-payload-interface', [
            'name' => $this->payloadInterface,
            '--namespace' => trim(str_replace($this->rootNamespace(), '', $this->payloadInterfaceNamespace), '\\'),
            '--package' => $this->option('package'),
            '--vendor' => $this->option('vendor'),
            '--force' => $this->option('force'),
        ]);

        return false;
    }

    protected function replaceClass($stub, $name): array|string
    {
        $stub = str_replace(
            ['DummyPayloadInterface', 'DummyInterfaceNamespace'],
            [$this->payloadInterface, Str::replace('\\\\', '\\', $this->payloadInterfaceNamespace)],
            $stub
        );

        return parent::replaceClass($stub, $name);
    }

    protected function getOptions(): array
    {
        return [
            [
                'namespace',
                'N',
                InputOption::VALUE_REQUIRED,
                'The namespace to place the Payload class.',
                'Processes\Payloads',
            ],
            [
                'package',
                'package',
                InputOption::VALUE_OPTIONAL,
                'Generate Payload class in specified package.',
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
                'Create the Payload class even if the file already exists.',
            ],
        ];
    }
}
