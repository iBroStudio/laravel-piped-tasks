<?php

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Events;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\TestSupport\Actions\AbortProcessTaskFakeAction;
use IBroStudio\TestSupport\Actions\RunFakeActionLongName;
use IBroStudio\TestSupport\Actions\SkipTaskFakeAction;
use IBroStudio\TestSupport\Processes\LongFakeNameProcess;
use IBroStudio\TestSupport\Processes\MultipleProcess;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('can run an eloquent process', function () {
    $resultPayload = LongFakeNameProcess::process();

    expect($resultPayload)->toBeInstanceOf(Payload::class)
        ->and($resultPayload->process)->toBeInstanceOf(LongFakeNameProcess::class);

    $process = LongFakeNameProcess::first();

    expect($process->state)->toBe(ProcessStatesEnum::COMPLETED)
        ->and($process->taskModels->first()->state)->toBe(ProcessStatesEnum::COMPLETED);
});

it('runs eloquent process with events', function () {
    Event::fake();
    Queue::fake();
    LongFakeNameProcess::process();

    Event::assertDispatched(Events\PipelineStarted::class);
    Event::assertDispatched(Events\PipelineFinished::class);

    Event::assertDispatched(Events\PipeExecutionStarted::class, function (Events\PipeExecutionStarted $event) {
        return $event->pipe === RunFakeActionLongName::class;
    });
    Event::assertDispatched(Events\PipeExecutionFinished::class, function (Events\PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeActionLongName;
    });
});

it('can execute a process within a process', function () {
    MultipleProcess::process();

    $main_process = Process::whereClass(MultipleProcess::class)->first();
    $child_process = Process::whereClass(LongFakeNameProcess::class)->first();

    expect($main_process->state)->toBe(ProcessStatesEnum::COMPLETED)
        ->and($child_process->state)->toBe(ProcessStatesEnum::COMPLETED);

    Task::all()->each(function (Task $task) {
        expect($task->state)->toBe(ProcessStatesEnum::COMPLETED);
    });
});

it('can skip a task', function () {
    Config::set('piped-tasks', [
        'tasks' => [
            LongFakeNameProcess::class => [
                'prepend' => [SkipTaskFakeAction::class],
            ],
        ],
    ]);

    LongFakeNameProcess::process();
    $process = LongFakeNameProcess::first();

    expect($process->state)->toBe(ProcessStatesEnum::COMPLETED)
        ->and($process->taskModels->first()->state)->toBe(ProcessStatesEnum::SKIPPED)
        ->and($process->taskModels->last()->state)->toBe(ProcessStatesEnum::COMPLETED);
});

it('can abort a process', function () {
    Config::set('piped-tasks', [
        'tasks' => [
            LongFakeNameProcess::class => [
                'prepend' => [AbortProcessTaskFakeAction::class],
            ],
        ],
    ]);

    LongFakeNameProcess::process();
    $process = LongFakeNameProcess::first();

    expect($process->state)->toBe(ProcessStatesEnum::ABORTED)
        ->and($process->taskModels->first()->state)->toBe(ProcessStatesEnum::ABORTED)
        ->and($process->taskModels->last()->state)->toBe(ProcessStatesEnum::PENDING);
});
