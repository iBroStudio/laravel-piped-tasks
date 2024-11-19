<?php

use IBroStudio\PipedTasks\Actions\RunProcess;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\TestSupport\Actions\RunFakeAction;
use IBroStudio\TestSupport\Actions\RunFakeAction2;
use IBroStudio\TestSupport\Processes\FakeProcess;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionFinished;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionStarted;
use MichaelRubel\EnhancedPipeline\Events\PipelineFinished;
use MichaelRubel\EnhancedPipeline\Events\PipelineStarted;

it('can run a simple process', function () {
    $process = FakeProcess::process();

    expect($process)->toBeInstanceOf(Payload::class)
        ->and($process->getProcess())->toBeInstanceOf(FakeProcess::class);
});

it('can run a simple process with events', function () {
    Event::fake();
    FakeProcess::process();

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);
});

it('can run a task', function () {
    Event::fake();

    FakeProcess::process();

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeAction::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeAction;
    });
});

it('can run an injected task added from config', function () {
    Event::fake();
    Config::set('piped-tasks', [
        'tasks' => [
            FakeProcess::class => [
                'prepend' => [],
                'append' => [RunFakeAction2::class],
            ],
        ],
    ]);
    FakeProcess::process();

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeAction2::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeAction2;
    });
});

it('can run a pre-added injected task from config', function () {
    Event::fake();
    Config::set('piped-tasks', [
        'tasks' => [
            FakeProcess::class => [
                'prepend' => [RunFakeAction2::class],
                'append' => [],
            ],
        ],
    ]);
    FakeProcess::process([]);

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeAction2::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeAction2;
    });
});

it('can handle the payload of a process', function () {
    Event::fake();
    $process = FakeProcess::process(['value1', ['value2', 'value3']]);

    expect($process)->toBeInstanceOf(Payload::class)
        // @phpstan-ignore-next-line
        ->and($process->getProperty1())->toBe('value1')
        // @phpstan-ignore-next-line
        ->and($process->getProperty2())->toMatchArray(['value2', 'value3']);

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);
    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeAction::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeAction;
    });
});

it('can run process async', function () {
    Event::fake();
    FakeProcess::process(async: true);

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);
});

it('can run process async via queue', function () {
    Queue::fake();

    expect(
        FakeProcess::process(async: true)
    )->toBeInstanceOf(PendingDispatch::class);

    RunProcess::assertPushed();
});
