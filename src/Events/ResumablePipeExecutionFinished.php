<?php

declare(strict_types=1);

namespace IBroStudio\PipedTasks\Events;

use IBroStudio\PipedTasks\Models\Process;

class ResumablePipeExecutionFinished
{
    public function __construct(public Process $process, public $pipe, public $passable)
    {
        //
    }
}
