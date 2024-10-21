<?php

namespace IBroStudio\PipedTasks;

use IBroStudio\PipedTasks\Concerns\IsProcess;
use IBroStudio\PipedTasks\Contracts\ProcessContract;

class SimpleProcess implements ProcessContract
{
    use IsProcess;
}
