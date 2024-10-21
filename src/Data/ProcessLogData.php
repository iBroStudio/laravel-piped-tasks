<?php

namespace IBroStudio\PipedTasks\Data;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class ProcessLogData extends Data
{
    public function __construct(
        public string $logName,
        //public Model $causedBy,
        public Model $performedOn,
        public ProcessStatesEnum $event,
        public string $description,
        public array $properties = [],
    ) {}
}
