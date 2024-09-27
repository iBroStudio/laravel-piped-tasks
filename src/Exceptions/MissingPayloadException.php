<?php

namespace IBroStudio\PipedTasks\Exceptions;

use Exception;

class MissingPayloadException extends Exception
{
    public function __construct()
    {
        parent::__construct('Incorrect process name. Use "<Action><Domain>Process" format');
    }
}
