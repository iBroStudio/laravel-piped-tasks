<?php

namespace IBroStudio\PipedTasks\Exceptions;

use Exception;
use IBroStudio\PipedTasks\Actions\RunProcess;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessContract;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;

class PauseProcessException extends Exception
{
    public function __construct(
        ProcessContract|ProcessModelContract|null $childProcess = null,
        ?Payload $payload = null
    ) {
        parent::__construct();

        if (! is_null($childProcess)) {
            RunProcess::run(
                process: $childProcess,
                payload: $payload,
            );
        }
    }
}
