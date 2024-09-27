<?php

namespace IBroStudio\PipedTasks\Tests\Support;

use Closure;

class FakeTask
{
    public function __invoke(FakePayload $payload, Closure $next): mixed
    {
        return $next($payload);
    }
}
