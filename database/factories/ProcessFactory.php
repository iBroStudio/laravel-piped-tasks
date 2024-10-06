<?php

namespace IBroStudio\PipedTasks\Database\Factories;

use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessFactory extends Factory
{
    protected $model = Process::class;

    public function definition(): array
    {
        return [
            'state' => 'pending',
            'payload' => serialize(['test']),
            'ended_at' => null,
        ];
    }
}
