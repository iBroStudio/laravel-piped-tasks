<?php

namespace IBroStudio\PipedTasks\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

use function IBroStudio\PathSelectPrompt\pathselect;
use function Laravel\Prompts\error;
use function Laravel\Prompts\form;

class MakeProcessCommand extends BaseGeneratorCommand
{
    protected $name = 'make:piped-process';

    public $description = 'Generate a process for piped tasks';

    protected $type = 'Process';

    protected static string $stub = '/stubs/process.stub';

    protected string $payloadClass;

    protected string $payloadNamespace;

    public function handle(): bool
    {
        if (! $this->checkName()) {
            error('Incorrect process name. Use "<Action><Domain>Process" format');

            return true;
        }

        $responses = form()
            ->intro('PIPED TASKS:new process')

            ->select(
                label: 'What kind of process do you want to create?',
                options: [
                    'process' => 'Loggable and resumable Process',
                    'process-simple' => 'Simple Process',
                ],
                name: 'process'
            )

            ->select(
                label: 'Where do you want to create the process?',
                options: [
                    'app' => 'in a Laravel application',
                    'package' => 'in a Laravel package',
                ],
                name: 'location'
            )

            ->add(function ($responses) {
                if ($responses['location'] === 'package') {
                    return pathselect(
                        label: 'Locate your package',
                        root: base_path(),
                        default: base_path('vendor'),
                        validate: function (string $value) {
                            $this->input->setOption('package', $value);

                            return match (true) {
                                ! $this->checkPackage() => "{$value} is not a valid package path.",
                                default => null
                            };
                        }
                    );
                }
            }, name: 'package')

            ->select(
                label: 'Force creation?',
                options: [
                    false => 'no',
                    true => 'yes',
                ],
                name: 'force'
            )

            ->submit();

        static::$stub = "/stubs/{$responses['process']}.stub";

        $this->input->setOption('package', $responses['package'] ?? null);
        $this->input->setOption('namespace', 'Processes');
        $this->input->setOption('force', $responses['force']);

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

        return false;
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
                'process',
                'process',
                InputOption::VALUE_REQUIRED,
                'Type of Process.',
                'Process',
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

    protected function checkPackage(): bool
    {
        $composer = Str::of($this->getRootPath())
            ->before('/src')
            ->append('/composer.json');

        return File::exists($composer);
    }

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => [
                'What should the process be named?', 'E.g. InstallServerProcess',
            ],
        ];
    }
}
