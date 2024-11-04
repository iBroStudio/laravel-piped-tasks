<?php

use IBroStudio\PipedTasks\Actions\UpdateProcessStateAction;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\TestSupport\Processes\LongFakeNameProcess;
use IBroStudio\TestSupport\Processes\MultipleProcess;
use IBroStudio\TestSupport\Processes\Tasks\LongFakeActionTask;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionFinished;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionStarted;
use MichaelRubel\EnhancedPipeline\Events\PipelineFinished;
use MichaelRubel\EnhancedPipeline\Events\PipelineStarted;
use Spatie\QueueableAction\Testing\QueueableActionFake;

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
        return $event->pipe === LongFakeActionTask::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof LongFakeActionTask;
    });

    QueueableActionFake::assertPushed(UpdateProcessStateAction::class);
});

it('can execute a process within a process', function () {
    $resultPayload = MultipleProcess::process();

    $process = MultipleProcess::first();
dd($process->taskModels->toArray());
    expect($process->state)->toBe(ProcessStatesEnum::COMPLETED);
});
