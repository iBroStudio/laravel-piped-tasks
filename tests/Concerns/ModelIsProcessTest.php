<?php

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\TestSupport\Actions\RunFakeActionLongName;
use IBroStudio\TestSupport\Processes\LongFakeNameProcess;
use IBroStudio\TestSupport\Processes\MultipleProcess;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionFinished;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionStarted;
use MichaelRubel\EnhancedPipeline\Events\PipelineFinished;
use MichaelRubel\EnhancedPipeline\Events\PipelineStarted;

it('can run an eloquent process', function () {
    $resultPayload = LongFakeNameProcess::process();

    expect($resultPayload)->toBeInstanceOf(Payload::class)
        ->and($resultPayload->getProcess())->toBeInstanceOf(LongFakeNameProcess::class);

    $process = LongFakeNameProcess::first();

    expect($process->state)->toBe(ProcessStatesEnum::COMPLETED)
        ->and($process->taskModels->first()->state)->toBe(ProcessStatesEnum::COMPLETED);
});

it('runs eloquent process with events', function () {
    Event::fake();
    Queue::fake();
    LongFakeNameProcess::process();

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeActionLongName::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
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
