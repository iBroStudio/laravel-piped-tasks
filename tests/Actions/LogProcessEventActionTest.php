<?php

use IBroStudio\PipedTasks\Actions\LogProcess;
use IBroStudio\PipedTasks\Actions\UpdateProcessState;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\TestSupport\Models\ProcessableFakeModel;
use IBroStudio\TestSupport\Processes\LongFakeNameProcess;
use IBroStudio\TestSupport\Processes\ResumableFakeProcess;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;

it('can run the log process action', function () {
    Queue::fake();

    $payload = LongFakeNameProcess::makePayload();
    $payload->process = LongFakeNameProcess::makeProcess($payload);

    UpdateProcessState::run(
        process: $payload->process,
        state: ProcessStatesEnum::COMPLETED
    );

    LogProcess::dispatch(
        process: $payload->process,
        payload: $payload
    );

    LogProcess::assertPushed();
});

it('do not log if config log_processes is false', function () {
    LongFakeNameProcess::process();
    $logs = Activity::inLog('long-fake-name')->get();
    expect($logs)->toHaveCount(0);
});

it('can log a process event', function () {
    config(['piped-tasks.log_processes' => true]);
    LongFakeNameProcess::process();
    $logs = Activity::inLog('long-fake-name')->get();

    expect($logs->get(0)->description)->toBe('long fake name started')
        ->and($logs->get(0)->subject_type)->toBe(LongFakeNameProcess::class)
        ->and($logs->get(0)->subject_id)->toBe(1)
        ->and($logs->get(0)->event)->toBe(ProcessStatesEnum::STARTED->getLabel())
        ->and($logs->get(1)->description)->toBe('run fake action long name started')
        ->and($logs->get(1)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(2)->description)->toBe('run fake action long name completed')
        ->and($logs->get(2)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(3)->description)->toBe('long fake name completed')
        ->and($logs->get(3)->event)->toBe(ProcessStatesEnum::COMPLETED->getLabel());
});

it('can log a resumed process event', function () {
    config(['piped-tasks.log_processes' => true]);
    $resultPayload = ResumableFakeProcess::process();
    // @phpstan-ignore-next-line
    ResumableFakeProcess::resume($resultPayload->process->id);

    $logs = Activity::inLog('personalized-name')->get();

    expect($logs->get(0)->description)->toBe('resumable fake started')
        ->and($logs->get(0)->subject_type)->toBe(ProcessableFakeModel::class)
        ->and($logs->get(0)->subject_id)->toBe(1)
        ->and($logs->get(0)->event)->toBe(ProcessStatesEnum::STARTED->getLabel())
        ->and($logs->get(1)->description)->toBe('run fake action resumable started')
        ->and($logs->get(1)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(2)->description)->toBe('run fake action resumable waiting')
        ->and($logs->get(2)->event)->toBe(ProcessStatesEnum::WAITING->getLabel())
        ->and($logs->get(3)->description)->toBe('run fake action resumable completed')
        ->and($logs->get(3)->event)->toBe(ProcessStatesEnum::RESUME->getLabel())
        ->and($logs->get(4)->description)->toBe('run fake action resumable process2 started')
        ->and($logs->get(4)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(5)->description)->toBe('run fake action resumable process2 completed')
        ->and($logs->get(5)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(6)->description)->toBe('resumable fake completed')
        ->and($logs->get(6)->event)->toBe(ProcessStatesEnum::COMPLETED->getLabel());
});

it('can log payload properties', function () {
    config(['piped-tasks.log_processes' => true]);
    $resultPayload = ResumableFakeProcess::process();
    // @phpstan-ignore-next-line
    ResumableFakeProcess::resume($resultPayload->process->id);

    $logs = Activity::inLog('personalized-name')->get();

    expect($logs->get(3)->properties->first())->toBe('changed')
        ->and($logs->get(3)->properties->last())->toBeArray()
        ->and($logs->last()->properties->first())->toBe('re-changed')
        ->and($logs->last()->properties->last())->toBeArray();
});
