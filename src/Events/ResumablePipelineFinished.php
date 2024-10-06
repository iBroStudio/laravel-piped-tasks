<?php

declare(strict_types=1);

namespace IBroStudio\PipedTasks\Events;

use Closure;
use IBroStudio\PipedTasks\Models\Process;

class ResumablePipelineFinished
{
    public function __construct(
        public Process $process,
        public Closure $destination,
        public $passable,
        public array $pipes,
        public bool $useTransaction,
        public $result,
    ) {
        //
    }
}
