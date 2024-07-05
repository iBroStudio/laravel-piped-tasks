<?php

namespace IBroStudio\PipedTasks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IBroStudio\PipedTasks\PipedTasks
 */
class PipedTasks extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \IBroStudio\PipedTasks\PipedTasks::class;
    }
}
