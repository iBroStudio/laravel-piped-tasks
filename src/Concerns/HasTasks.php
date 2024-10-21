<?php

namespace IBroStudio\PipedTasks\Concerns;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Task;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTasks
{
    public function taskModels(): HasMany
    {
        return $this->hasMany(Task::class, 'process_id');
    }

    public function taskModel(string $class): Task
    {
        return $this->taskModels()->where('class', $class)->first();
    }

    public function currentTask(): Task
    {
        return $this->taskModels()->where('state', ProcessStatesEnum::PROCESSING)->first();
    }

    public function waitingTask(): Task
    {
        return $this->taskModels()->where('state', ProcessStatesEnum::WAITING)->first();
    }
}
