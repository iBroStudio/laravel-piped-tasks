<?php

namespace IBroStudio\PipedTasks\Commands;

use IBroStudio\PipedTasks\Exceptions\BadProcessNameException;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class MakeProcessCommand extends BaseGeneratorCommand
{
    protected $name = 'make:piped-process';

    public $description = 'Generate a process for piped tasks';

    protected $type = 'Process';

    protected static string $stub = '/stubs/process.stub';

    protected string $payloadClass;

    protected string $payloadNamespace;

    /**
     * @throws BadProcessNameException
     */
    public function handle(): bool
    {
        if (! $this->checkName()) {
            throw new BadProcessNameException();
        }

        $this->payloadClass = Str::of($this->getNameInput())
            ->before('Process')
            ->append('Payload')
            ->split('/\//')
            ->last();

        $this->payloadNamespace =
            str_replace('\\\\', '\\',
                Str::of($this->getDefaultNamespace($this->rootNamespace()))
                    ->append('\Payloads')
            );

        parent::handle();

        $this->call('make:piped-payload', [
            'name' => $this->payloadClass,
            '--namespace' => trim(str_replace($this->rootNamespace(), '', $this->payloadNamespace), '\\'),
            '--package' => $this->option('package'),
            '--vendor' => $this->option('vendor'),
            '--force' => $this->option('force'),
        ]);

        return Command::SUCCESS;
    }

    protected function replaceClass($stub, $name): array|string
    {
        $stub = str_replace(
            ['DummyPayloadClass', 'DummyPayloadNamespace'],
            [$this->payloadClass, $this->payloadNamespace],
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
                'The namespace (under \App) to place the Process class.',
                'Processes',
            ],
            [
                'package',
                'package',
                InputOption::VALUE_OPTIONAL,
                'Generate Process class in specified package.',
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
                'Create the Process class even if the file already exists.',
            ],
        ];
    }

    private function checkName(): bool
    {
        return Str::of(
            Str::of($this->getNameInput())
                ->split('/\//')
                ->last()
        )
            ->split('/(?=[A-Z])/')
            ->last()
            === 'Process';
    }
}
