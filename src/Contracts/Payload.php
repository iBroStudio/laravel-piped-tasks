<?php

namespace IBroStudio\PipedTasks\Contracts;

use IBroStudio\PipedTasks\Models\Process;

/**
 * @property Process|ProcessContract|ProcessModelContract $process
 */
interface Payload
{
    public function setProcess(Process|ProcessContract|ProcessModelContract $process): void;

    public function getProcess(): Process|ProcessContract|ProcessModelContract;

    public function toArray(): array;
}
