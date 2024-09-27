<?php

namespace IBroStudio\PipedTasks\Tests\Support;

use IBroStudio\PipedTasks\Process;

class FakeProcess extends Process
{
    protected bool $withEvents = true;
}
