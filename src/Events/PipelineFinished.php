<?php

namespace IBroStudio\PipedTasks\Events;

use Closure;

class PipelineFinished
{
    /**
     * @param  mixed  $passable
     * @param  mixed  $result
     */
    public function __construct(
        public Closure $destination,
        public $passable,
        public array $pipes,
        public bool $useTransaction,
        public $result,
    ) {
        //
    }
}
