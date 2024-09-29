<?php

use IBroStudio\PipedTasks\Payload;
use IBroStudio\PipedTasks\Process;
use IBroStudio\PipedTasks\Tests\Support\Processes\FakeProcess;
use IBroStudio\PipedTasks\Tests\Support\Processes\Payloads\FakePayload;
use IBroStudio\PipedTasks\Tests\Support\Processes\Tasks\FakeTask;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionFinished;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionStarted;
use MichaelRubel\EnhancedPipeline\Events\PipelineFinished;
use MichaelRubel\EnhancedPipeline\Events\PipelineStarted;

it('can run a process', function () {
    expect(
        (new FakeProcess)->run(new FakePayload)
    )->toBeInstanceOf(Payload::class);
});

it('can run a process with events', function () {
    Event::fake();
    (new FakeProcess)->run(new FakePayload);

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);
});

it('can run a task', function () {
    Event::fake();

    $process = new class extends Process
    {
        protected array $tasks = [FakeTask::class];

        protected bool $withEvents = true;
    };

    expect($process->run(new FakePayload))->toBeInstanceOf(Payload::class);

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === FakeTask::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof FakeTask;
    });
});

it('can run an injected task added from config', function () {
    Event::fake();
    Config::set('piped-tasks', [
        'tasks' => [
            FakeProcess::class => [
                'prepend' => [],
                'append' => [FakeTask::class],
            ],
        ],
    ]);
    (new FakeProcess)->run(new FakePayload);

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === FakeTask::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof FakeTask;
    });
});

it('can run a pre-added injected task from config', function () {
    Event::fake();
    Config::set('piped-tasks', [
        'tasks' => [
            FakeProcess::class => [
                'prepend' => [FakeTask::class],
                'append' => [],
            ],
        ],
    ]);
    (new FakeProcess)->run(new FakePayload);

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === FakeTask::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof FakeTask;
    });
});

it('can handle a process', function () {
    Event::fake();
    Config::set('piped-tasks', [
        'tasks' => [
            FakeProcess::class => [
                'prepend' => [],
                'append' => [FakeTask::class],
            ],
        ],
    ]);
    $process = FakeProcess::handle(['value1', ['value2', 'value3']]);

    expect($process)->toBeInstanceOf(Payload::class)
        ->and($process->getProperty1())->toBe('value1')
        ->and($process->getProperty2())->toMatchArray(['value2', 'value3']);

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);
    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === FakeTask::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof FakeTask;
    });
});
