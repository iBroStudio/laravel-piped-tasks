<?php

namespace IBroStudio\PipedTasks\Models;

use IBroStudio\PipedTasks\Concerns\CanBeResumed;
use IBroStudio\PipedTasks\Concerns\HasLogData;
use IBroStudio\PipedTasks\Concerns\HasLogs;
use IBroStudio\PipedTasks\Concerns\HasProcessable;
use IBroStudio\PipedTasks\Concerns\HasTasks;
use IBroStudio\PipedTasks\Concerns\ModelIsProcess;
use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\ProcessModelContract;
use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $class
 * @property Payload $payload
 * @property ProcessStatesEnum $state
 * @property string $log_batch_uuid
 */
class Process extends Model implements ProcessModelContract
{
    use CanBeResumed;
    use HasFactory;
    use HasLogData;
    use HasLogs;
    use HasProcessable;
    use HasTasks;
    use ModelIsProcess;

    protected $table = 'processes';

    protected $fillable = [
        'class',
        'payload',
        'state',
        'log_batch_uuid',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => ProcessStatesEnum::class,
            'started_at' => 'timestamp',
            'ended_at' => 'timestamp',
        ];
    }

    protected function payload(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => unserialize($value),
        );
    }
}
