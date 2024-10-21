<?php

namespace IBroStudio\PipedTasks\Database\Factories;

use IBroStudio\PipedTasks\Models\Process;
use IBroStudio\TestSupport\Processes\ResumableFakeProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessFactory extends Factory
{
    protected $model = Process::class;

    public function definition(): array
    {
        return [
            'class' => ResumableFakeProcess::class,
            'state' => 'pending',
            'payload' => serialize(['test']),
            'ended_at' => null,
        ];
    }
}
