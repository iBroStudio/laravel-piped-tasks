<?php

use IBroStudio\PipedTasks\Data\ProcessLogData;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\TestSupport\Processes\LongFakeNameProcess;
use IBroStudio\TestSupport\Processes\Payloads\LongFakeNamePayload;
use IBroStudio\TestSupport\Processes\ResumableFakeProcess;

it('can retrieve process log data', function () {
    expect(
        LongFakeNameProcess::factory()->create(['class' => LongFakeNameProcess::class])
            ->logData(new LongFakeNamePayload)
    )->toBeInstanceOf(ProcessLogData::class);
});

it('can retrieve task log data', function () {
    $task = Task::factory()
        ->for(Process::factory())
        ->create();

    expect(
        $task->process
            ->logTaskData($task, new LongFakeNamePayload)
    )->toBeInstanceOf(ProcessLogData::class);
});

it('can retrieve the log name from the process class', function () {
    expect(
        ResumableFakeProcess::factory()->create(['class' => ResumableFakeProcess::class])
            ->logData(new LongFakeNamePayload)
            ->logName
    )->toBe('personalized-name');
});

it('can infer the log name if the process class gives null', function () {
    expect(
        LongFakeNameProcess::factory()->create(['class' => LongFakeNameProcess::class])
            ->logData(new LongFakeNamePayload)
            ->logName
    )->toBe('long-fake-name');
});
