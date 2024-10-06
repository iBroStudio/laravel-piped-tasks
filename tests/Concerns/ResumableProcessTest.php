<?php

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\StatesEnum;
use IBroStudio\PipedTasks\Events\ResumablePipeExecutionFinished;
use IBroStudio\PipedTasks\Events\ResumablePipeExecutionStarted;
use IBroStudio\PipedTasks\Events\ResumablePipelineFinished;
use IBroStudio\PipedTasks\Events\ResumablePipelineStarted;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\PipedTasks\Tests\Support\Processes\Payloads\ResumablePayload;
use IBroStudio\PipedTasks\Tests\Support\Processes\ResumableProcess;
use IBroStudio\PipedTasks\Tests\Support\Processes\Tasks\ResumableFakeTask;
use IBroStudio\PipedTasks\Tests\Support\Processes\Tasks\ResumableFakeTask2;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\get;

it('can pause a process', function () {
    Event::fake();
    $process = new ResumableProcess;

    $payload = new ResumablePayload('ok', Task::factory()
        ->for(Process::factory()->create(['class' => ResumableProcess::class]))
        ->create());
    $run = $process->run($payload);

    expect($run)->toBeInstanceOf(Payload::class)
        ->and($run->getProcessModel()->state)->toBe(StatesEnum::PENDING);

    Event::assertDispatched(ResumablePipelineStarted::class);
    Event::assertNotDispatched(ResumablePipelineFinished::class);

    Event::assertDispatched(ResumablePipeExecutionStarted::class, function (ResumablePipeExecutionStarted $event) {
        return $event->pipe === ResumableFakeTask::class;
    });
    Event::assertDispatched(ResumablePipeExecutionFinished::class, function (ResumablePipeExecutionFinished $event) {
        return $event->pipe instanceof ResumableFakeTask;
    });

    Event::assertNotDispatched(ResumablePipeExecutionStarted::class, function (ResumablePipeExecutionStarted $event) {
        return $event->pipe === ResumableFakeTask2::class;
    });
    Event::assertNotDispatched(ResumablePipeExecutionFinished::class, function (ResumablePipeExecutionFinished $event) {
        return $event->pipe instanceof ResumableFakeTask2;
    });
});

it('can resume a process', function () {
    Event::fake();
    $process = new ResumableProcess;

    $payload = new ResumablePayload('ok', Task::factory()
        ->for(Process::factory()->create(['class' => ResumableProcess::class]))
        ->create());

    $process = $process->makeAndBindProcessModel($payload, [
        ResumableFakeTask::class,
        ResumableFakeTask2::class,
    ]);

    $task = $process->getProcessModel()->task(ResumableFakeTask::class);
    $task->update(['state' => StatesEnum::COMPLETED]);

    $resume = Process::resume($process->getProcessModel()->id);

    expect($resume)->toBeInstanceOf(Payload::class)
        ->and($resume->getProcessModel()->state)->toBe(StatesEnum::COMPLETED);

    Event::assertDispatched(ResumablePipelineStarted::class);
    Event::assertDispatched(ResumablePipelineFinished::class);

    Event::assertNotDispatched(ResumablePipeExecutionStarted::class, function (ResumablePipeExecutionStarted $event) {
        return $event->pipe === ResumableFakeTask::class;
    });
    Event::assertNotDispatched(ResumablePipeExecutionFinished::class, function (ResumablePipeExecutionFinished $event) {
        return $event->pipe instanceof ResumableFakeTask;
    });

    Event::assertDispatched(ResumablePipeExecutionStarted::class, function (ResumablePipeExecutionStarted $event) {
        return $event->pipe === ResumableFakeTask2::class;
    });
    Event::assertDispatched(ResumablePipeExecutionFinished::class, function (ResumablePipeExecutionFinished $event) {
        return $event->pipe instanceof ResumableFakeTask2;
    });
});

it('can retrieve changed payload when resume process', function () {
    Event::fake();
    $process = new ResumableProcess;

    $payload = new ResumablePayload('ok');
    $run = $process->run($payload);

    $resume = Process::resume($run->getProcessModel()->id);

    expect($resume->getProperty1())->toBe('changed')
        ->and($resume->getProperty2())->toBeInstanceOf(Task::class);
});

it('can resume a process from a signed url', function () {
    Event::fake();
    $process = new ResumableProcess;

    $payload = new ResumablePayload('ok', Task::factory()
        ->for(Process::factory()->create(['class' => ResumableProcess::class]))
        ->create());

    $process = $process->makeAndBindProcessModel($payload, [
        ResumableFakeTask::class,
        ResumableFakeTask2::class,
    ]);

    $task = $process->getProcessModel()->task(ResumableFakeTask::class);
    $task->update(['state' => StatesEnum::COMPLETED]);

    get(
        $process->getProcessModel()->resumeUrl()
    )->assertSuccessful();

    Event::assertDispatched(ResumablePipelineStarted::class);
    Event::assertDispatched(ResumablePipelineFinished::class);

    Event::assertNotDispatched(ResumablePipeExecutionStarted::class, function (ResumablePipeExecutionStarted $event) {
        return $event->pipe === ResumableFakeTask::class;
    });
    Event::assertNotDispatched(ResumablePipeExecutionFinished::class, function (ResumablePipeExecutionFinished $event) {
        return $event->pipe instanceof ResumableFakeTask;
    });

    Event::assertDispatched(ResumablePipeExecutionStarted::class, function (ResumablePipeExecutionStarted $event) {
        return $event->pipe === ResumableFakeTask2::class;
    });
    Event::assertDispatched(ResumablePipeExecutionFinished::class, function (ResumablePipeExecutionFinished $event) {
        return $event->pipe instanceof ResumableFakeTask2;
    });

    expect($process->getProcessModel()->refresh()->state)->toBe(StatesEnum::COMPLETED);
});
