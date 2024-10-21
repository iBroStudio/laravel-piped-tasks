<?php

namespace IBroStudio\PipedTasks\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Processable
{
    public function processes(): MorphMany;
}
