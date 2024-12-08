<?php

namespace IBroStudio\PipedTasks\Exceptions;

use Exception;

class SkipTaskException extends Exception
{
    public function __construct(public mixed $next)
    {
        parent::__construct();
    }
}
