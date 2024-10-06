<?php

namespace IBroStudio\PipedTasks\Tests\Support\Processes\Tasks;

use Closure;
use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\PipedTasks\PauseProcess;
use IBroStudio\PipedTasks\Tests\Support\Processes\Payloads\ResumablePayload;
use IBroStudio\PipedTasks\Tests\Support\Processes\ResumableProcess;

class ResumableFakeTask
{
    public function __invoke(ResumablePayload $payload, Closure $next): mixed
    {
        $payload->setProperty1('changed');

        $payload->setProperty2(
            Task::factory()
                ->for(Process::factory()->create(['class' => ResumableProcess::class]))
                ->create()
        );

        return new PauseProcess;
    }
}
