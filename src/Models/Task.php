<?php

namespace IBroStudio\PipedTasks\Models;

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $class
 * @property ProcessStatesEnum $state
 */
class Task extends Model
{
    use HasFactory;

    protected $table = 'processes_tasks';

    public $timestamps = false;

    protected $fillable = [
        'process_id',
        'class',
        'state',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    protected function casts(): array
    {
        return [
            'state' => ProcessStatesEnum::class,
        ];
    }
}
