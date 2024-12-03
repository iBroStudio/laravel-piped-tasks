<?php

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\TestSupport\Models\ProcessableFakeModel;
use IBroStudio\TestSupport\Processes\FakeProcess;
use IBroStudio\TestSupport\Processes\LongFakeNameProcess;
use IBroStudio\TestSupport\Processes\ResumableFakeProcess;
use MichaelRubel\EnhancedPipeline\Events\PipelineFinished;
use MichaelRubel\EnhancedPipeline\Events\PipelineStarted;

use function Pest\Laravel\assertModelExists;

it('can have processes', function () {
    $processable = ProcessableFakeModel::factory()
        ->hasProcesses(Process::class)
        ->create();

    assertModelExists(
        $processable->processes()->first()
    );
});

it('can attach a process', function () {
    $processable = ProcessableFakeModel::factory()->create();
    $process = Process::factory()->create();
    $processable->processes()->save($process);

    expect($processable->processes()->first()->is($process))->toBeTrue();
});

it('allows processable to call process', function () {
    $processable = ProcessableFakeModel::factory()->create();

    $resultPayload = $processable->process(LongFakeNameProcess::class);

    expect($resultPayload)->toBeInstanceOf(Payload::class)
        ->and($resultPayload->process)->toBeInstanceOf(LongFakeNameProcess::class);

    $process = LongFakeNameProcess::first();

    expect($process->processable->is($processable))->toBe(true)
        ->and($process->state)->toBe(ProcessStatesEnum::COMPLETED)
        ->and($process->taskModels->first()->state)->toBe(ProcessStatesEnum::COMPLETED);
});

it('allows processable class to call statically process', function () {
    $resultPayload = ProcessableFakeModel::callProcess(ResumableFakeProcess::class);
    // @phpstan-ignore-next-line
    $resumePayload = ResumableFakeProcess::resume($resultPayload->process->id);
    $process = $resumePayload->process;

    expect($process->processable)->toBeInstanceOf(ProcessableFakeModel::class)
        ->and($process->processable->id)->toBe(1)
        ->and($process->state)->toBe(ProcessStatesEnum::COMPLETED)
        ->and($process->taskModels->first()->state)->toBe(ProcessStatesEnum::COMPLETED);
});

it('allows processable to call simple process', function () {
    Event::fake();
    $processable = ProcessableFakeModel::factory()->create();
    $resultPayload = $processable->process(FakeProcess::class);

    expect($resultPayload)->toBeInstanceOf(Payload::class)
        ->and($resultPayload->process)->toBeInstanceOf(FakeProcess::class);

    Event::assertDispatched(PipelineStarted::class);
    Event::assertDispatched(PipelineFinished::class);
});
