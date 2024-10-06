<?php

namespace IBroStudio\PipedTasks\Database\Factories;

use IBroStudio\PipedTasks\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'class' => $this->faker->word(),
            'state' => 'pending',
        ];
    }
}
