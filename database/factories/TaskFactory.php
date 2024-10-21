<?php

namespace IBroStudio\PipedTasks\Database\Factories;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Task;
use IBroStudio\TestSupport\Processes\Tasks\ResumableFakeTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'class' => ResumableFakeTask::class,
            'state' => ProcessStatesEnum::PENDING,
        ];
    }
}
