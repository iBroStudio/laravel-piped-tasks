<?php

namespace IBroStudio\PipedTasks\Models;

use IBroStudio\PipedTasks\Enums\StatesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $table = 'processes_tasks';

    public $timestamps = false;

    protected $fillable = [
        'process_id',
        'class',
        'state',
        'started_at',
        'ended_at',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    protected function casts(): array
    {
        return [
            'state' => StatesEnum::class,
            'started_at' => 'timestamp',
            'ended_at' => 'timestamp',
        ];
    }
}
