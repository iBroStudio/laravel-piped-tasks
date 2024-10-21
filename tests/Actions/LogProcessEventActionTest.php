<?php

use IBroStudio\PipedTasks\Actions\LogProcessAction;
use IBroStudio\PipedTasks\Actions\UpdateProcessStateAction;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\TestSupport\Models\ProcessableFakeModel;
use IBroStudio\TestSupport\Processes\LongFakeNameProcess;
use IBroStudio\TestSupport\Processes\ResumableFakeProcess;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueueableAction\ActionJob;
use Spatie\QueueableAction\Testing\QueueableActionFake;

it('can run the log process action', function () {
    Queue::fake();

    $payload = LongFakeNameProcess::makePayload();
    $process = LongFakeNameProcess::makeProcess($payload);
    $payload->setProcess($process);

    app(UpdateProcessStateAction::class)
        ->onQueue()
        ->execute(
            process: $process,
            state: ProcessStatesEnum::COMPLETED
        )->chain([
            new ActionJob(LogProcessAction::class, [$payload]),
        ]);

    QueueableActionFake::assertPushedWithChain(
        UpdateProcessStateAction::class,
        [LogProcessAction::class]
    );
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
        ->and($logs->get(1)->description)->toBe('long fake action started')
        ->and($logs->get(1)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(2)->description)->toBe('long fake action completed')
        ->and($logs->get(2)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(3)->description)->toBe('long fake name completed')
        ->and($logs->get(3)->event)->toBe(ProcessStatesEnum::COMPLETED->getLabel());
});

it('can log a resumed process event', function () {
    config(['piped-tasks.log_processes' => true]);
    $resultPayload = ResumableFakeProcess::process();
    // @phpstan-ignore-next-line
    ResumableFakeProcess::resume($resultPayload->getProcess()->id);

    $logs = Activity::inLog('personalized-name')->get();

    expect($logs->get(0)->description)->toBe('resumable fake started')
        ->and($logs->get(0)->subject_type)->toBe(ProcessableFakeModel::class)
        ->and($logs->get(0)->subject_id)->toBe(1)
        ->and($logs->get(0)->event)->toBe(ProcessStatesEnum::STARTED->getLabel())
        ->and($logs->get(1)->description)->toBe('resumable fake started')
        ->and($logs->get(1)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(2)->description)->toBe('resumable fake waiting')
        ->and($logs->get(2)->event)->toBe(ProcessStatesEnum::WAITING->getLabel())
        ->and($logs->get(3)->description)->toBe('resumable fake completed')
        ->and($logs->get(3)->event)->toBe(ProcessStatesEnum::RESUME->getLabel())
        ->and($logs->get(4)->description)->toBe('resumable fake task2 started')
        ->and($logs->get(4)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(5)->description)->toBe('resumable fake task2 completed')
        ->and($logs->get(5)->event)->toBe(ProcessStatesEnum::PROCESSING->getLabel())
        ->and($logs->get(6)->description)->toBe('resumable fake completed')
        ->and($logs->get(6)->event)->toBe(ProcessStatesEnum::COMPLETED->getLabel());
});

it('can log payload properties', function () {
    config(['piped-tasks.log_processes' => true]);
    $resultPayload = ResumableFakeProcess::process();
    // @phpstan-ignore-next-line
    ResumableFakeProcess::resume($resultPayload->getProcess()->id);

    $logs = Activity::inLog('personalized-name')->get();

    expect($logs->get(3)->properties->first())->toBe('changed')
        ->and($logs->get(3)->properties->last())->toBeArray()
        ->and($logs->last()->properties->first())->toBe('re-changed')
        ->and($logs->last()->properties->last())->toBeArray();
});
