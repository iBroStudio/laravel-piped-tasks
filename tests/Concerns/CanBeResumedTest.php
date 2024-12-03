<?php

use IBroStudio\PipedTasks\Actions\RunProcess;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Events\PipeExecutionPaused;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\TestSupport\Actions\RunFakeActionResumableProcess;
use IBroStudio\TestSupport\Actions\RunFakeActionResumableProcess2;
use IBroStudio\TestSupport\Models\FakeModel;
use IBroStudio\TestSupport\Processes\ResumableFakeProcess;
use IBroStudio\TestSupport\Processes\ResumableMultipleProcess;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionFinished;
use MichaelRubel\EnhancedPipeline\Events\PipeExecutionStarted;
use MichaelRubel\EnhancedPipeline\Events\PipelineFinished;
use MichaelRubel\EnhancedPipeline\Events\PipelineStarted;

use function Pest\Laravel\get;

it('can pause a process', function () {
    $resultPayload = ResumableFakeProcess::process();

    expect($resultPayload)->toBeInstanceOf(Payload::class)
        ->and($resultPayload->process)->toBeInstanceOf(ResumableFakeProcess::class);

    $process = ResumableFakeProcess::first();

    expect($process->state)->toBe(ProcessStatesEnum::WAITING)
        ->and($process->taskModels->first()->state)->toBe(ProcessStatesEnum::WAITING);
});

it('can emit events with paused process', function () {
    Event::fake();
    Queue::fake();
    ResumableFakeProcess::process();

    Event::assertDispatched(PipelineStarted::class);
    Event::assertNotDispatched(PipelineFinished::class);

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeActionResumableProcess::class;
    });
    Event::assertDispatched(PipeExecutionPaused::class, function (PipeExecutionPaused $event) {
        return $event->pipe instanceof RunFakeActionResumableProcess;
    });
    Event::assertNotDispatched(PipeExecutionFinished::class);

    Event::assertNotDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeActionResumableProcess2::class;
    });
    Event::assertNotDispatched(PipeExecutionPaused::class, function (PipeExecutionPaused $event) {
        return $event->pipe instanceof RunFakeActionResumableProcess2;
    });
    Event::assertNotDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeActionResumableProcess2;
    });
});

it('can resume a process', function () {
    Event::fake();
    $payload = ResumableFakeProcess::makePayload();
    $process = ResumableFakeProcess::makeProcess($payload);
    $process->update(['state' => ProcessStatesEnum::WAITING]);

    $task = $process->taskModel(RunFakeActionResumableProcess::class);
    $task->update(['state' => ProcessStatesEnum::WAITING]);

    ResumableFakeProcess::resume($process->id);

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);

    Event::assertNotDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeActionResumableProcess::class;
    });
    Event::assertNotDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeActionResumableProcess;
    });

    Event::assertDispatched(PipeExecutionStarted::class, function (PipeExecutionStarted $event) {
        return $event->pipe === RunFakeActionResumableProcess2::class;
    });
    Event::assertDispatched(PipeExecutionFinished::class, function (PipeExecutionFinished $event) {
        return $event->pipe instanceof RunFakeActionResumableProcess2;
    });

    $process = ResumableFakeProcess::first();

    expect($process->state)->toBe(ProcessStatesEnum::COMPLETED);

    $process->taskModels->each(function (Task $task) {
        expect($task->state)->toBe(ProcessStatesEnum::COMPLETED);
    });
});

it('can retrieve changed payload when resume process', function () {
    $resultPayload = ResumableFakeProcess::process();
    // @phpstan-ignore-next-line
    ResumableFakeProcess::resume($resultPayload->process->id);
    $payload = ResumableFakeProcess::first()->payload;

    // @phpstan-ignore-next-line
    expect($payload->property1)->toBe('changed')
        // @phpstan-ignore-next-line
        ->and($payload->property2)->toBeInstanceOf(FakeModel::class);
});

it('can run process via queue', function () {
    Queue::fake();

    expect(
        ResumableFakeProcess::process(async: true)
    )->toBeInstanceOf(PendingDispatch::class);

    RunProcess::assertPushed();
});

it('can resume a process from a signed url', function () {

    $resultPayload = ResumableFakeProcess::process();

    get($resultPayload->process->resumeUrl())->assertSuccessful();

    $process = ResumableFakeProcess::first();

    expect($process->state)->toBe(ProcessStatesEnum::COMPLETED);

    $process->taskModels->each(function (Task $task) {
        expect($task->state)->toBe(ProcessStatesEnum::COMPLETED);
    });
});

it('can execute a resumable process within a process', function () {
    ResumableMultipleProcess::process();

    $main_process = Process::whereClass(ResumableMultipleProcess::class)->first();
    $child_process = Process::whereClass(ResumableFakeProcess::class)->first();

    ResumableFakeProcess::resume($child_process->id);

    expect($main_process->refresh()->state)->toBe(ProcessStatesEnum::COMPLETED)
        ->and($child_process->refresh()->state)->toBe(ProcessStatesEnum::COMPLETED);

    Task::all()->each(function (Task $task) {
        expect($task->state)->toBe(ProcessStatesEnum::COMPLETED);
    });
});
