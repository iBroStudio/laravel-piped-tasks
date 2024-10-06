<?php

namespace IBroStudio\PipedTasks\Models;

use IBroStudio\PipedTasks\Enums\StatesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

/**
 * @property string $class
 * @property string $payload
 * @property StatesEnum $state
 */
class Process extends Model
{
    use HasFactory;

    protected $fillable = [
        'class',
        'payload',
        'state',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => StatesEnum::class,
            'started_at' => 'timestamp',
            'ended_at' => 'timestamp',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function task(string $class): Task
    {
        return $this->tasks()->where('class', $class)->first();
    }

    public function currentTask(): Task
    {
        return $this->tasks()->where('state', StatesEnum::PROCESSING)->first();
    }

    public function resumeUrl(): string
    {
        return URL::signedRoute('piped-tasks-process', ['process_id' => $this]);
    }

    public static function resume(int $process_id): mixed
    {
        $process = self::whereId($process_id)
            ->whereState(StatesEnum::PENDING)
            ->firstOrFail();

        return $process->class::resume($process);
    }
}
