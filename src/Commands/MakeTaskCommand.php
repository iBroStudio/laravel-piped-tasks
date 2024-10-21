<?php

namespace IBroStudio\PipedTasks\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputOption;

class MakeTaskCommand extends BaseGeneratorCommand
{
    protected $name = 'make:piped-task';

    public $description = 'Generate a piped task';

    protected $type = 'Task';

    protected static string $stub = '/stubs/task.stub';

    protected string $actionClass;

    protected string $actionNamespace;

    public function handle(): bool
    {
        if (! trim($this->option('payload'))) {

            $payload = null;

            while (is_null($payload)) {
                $payload = trim($this->ask('What payload do you want to use for this task?'));
            }

            $this->getDefinition()
                ->getOption('payload')
                ->setDefault($payload);
        }

        $sub = Str::of($this->getNameInput())->split('/\//');

        $this->actionClass =
            Str::of($this->getNameInput())
                ->before('Task')
                ->append('Action')
                ->when($sub->count() > 1, function (Stringable $string) {
                    return $string->afterLast('/');
                });

        $this->actionNamespace =
            str_replace('\\\\', '\\',
                Str::of($this->rootNamespace())
                    ->append('\\Actions')
            );

        parent::handle();

        $this->call('make:piped-action', [
            'name' => $this->actionClass,
            '--namespace' => trim(str_replace($this->rootNamespace(), '', $this->actionNamespace), '\\'),
            '--package' => $this->option('package'),
            '--vendor' => $this->option('vendor'),
            '--force' => $this->option('force'),
        ]);

        return false;
    }

    protected function replaceClass($stub, $name): array|string
    {
        $payloadNamespace =
            Str::of($this->getNamespace($name))
                ->before('Tasks')
                ->append('Payloads\\Contracts');

        $stub = str_replace(
            ['DummyActionClass', 'DummyActionNamespace', 'DummyPayloadContract', 'DummyPayloadNamespace'],
            [$this->actionClass, $this->actionNamespace, trim($this->option('payload')), $payloadNamespace],
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
                'The namespace to place the Task class.',
                'Processes\Tasks',
            ],
            [
                'payload',
                'payload',
                InputOption::VALUE_REQUIRED,
                'Payload used for this task.',
            ],
            [
                'package',
                'package',
                InputOption::VALUE_OPTIONAL,
                'Generate Task class in specified package.',
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
                'Create the Task class even if the file already exists.',
            ],
        ];
    }

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => [
                'What should the task be named?', 'E.g. CleanDirectoryTask',
            ],
        ];
    }
}
