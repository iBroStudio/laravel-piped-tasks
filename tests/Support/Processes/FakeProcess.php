<?php

namespace IBroStudio\PipedTasks\Tests\Support\Processes;

use IBroStudio\PipedTasks\Process;

class FakeProcess extends Process
{
    protected bool $withEvents = true;
}
