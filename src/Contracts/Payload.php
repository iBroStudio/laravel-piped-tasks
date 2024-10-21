<?php

namespace IBroStudio\PipedTasks\Contracts;

/**
 * @property ProcessContract|ProcessModelContract $process
 */
interface Payload
{
    public function setProcess(ProcessContract|ProcessModelContract $process): void;

    public function getProcess(): ProcessContract|ProcessModelContract;

    public function toArray(): array;
}
