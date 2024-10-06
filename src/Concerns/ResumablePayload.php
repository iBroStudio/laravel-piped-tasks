<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Queue\SerializesModels;

trait ResumablePayload
{
    use CanBindProcessModel;
    use SerializesModels;

    protected Process $process;
}
