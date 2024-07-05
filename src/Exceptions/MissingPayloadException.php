<?php

namespace IBroStudio\PipedTasks\Exceptions;

use Exception;
use Throwable;

class MissingPayloadException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Incorrect process name. Use "<Action><Domain>Process" format', $code, $previous);
    }
}
