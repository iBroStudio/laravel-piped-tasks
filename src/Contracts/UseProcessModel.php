<?php

namespace IBroStudio\PipedTasks\Contracts;

use IBroStudio\PipedTasks\Models\Process;

interface UseProcessModel
{
    public function bindProcessModel(Process $process): static;
}
