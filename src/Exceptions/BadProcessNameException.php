<?php

namespace IBroStudio\PipedTasks\Exceptions;

use Exception;

class BadProcessNameException extends Exception
{
    public function __construct()
    {
        parent::__construct('Incorrect process name. Use "<Action><Domain>Process" format');
    }
}
