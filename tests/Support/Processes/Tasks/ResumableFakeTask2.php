<?php

namespace IBroStudio\PipedTasks\Tests\Support\Processes\Tasks;

use Closure;
use IBroStudio\PipedTasks\Tests\Support\Processes\Payloads\ResumablePayload;

class ResumableFakeTask2
{
    public function __invoke(ResumablePayload $payload, Closure $next): mixed
    {
        return $next($payload);
    }
}
