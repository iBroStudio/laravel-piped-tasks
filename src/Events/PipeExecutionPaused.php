<?php

namespace IBroStudio\PipedTasks\Events;

class PipeExecutionPaused
{
    /**
     * @param  mixed  $pipe
     * @param  mixed  $passable
     */
    public function __construct(public $pipe, public $passable)
    {
        //
    }
}
