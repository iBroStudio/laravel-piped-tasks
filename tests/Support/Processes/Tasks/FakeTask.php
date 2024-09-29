<?php

namespace IBroStudio\PipedTasks\Tests\Support\Processes\Tasks;

use Closure;
use IBroStudio\PipedTasks\Tests\Support\Processes\Payloads\FakePayload;

class FakeTask
{
    public function __invoke(FakePayload $payload, Closure $next): mixed
    {
        return $next($payload);
    }
}
