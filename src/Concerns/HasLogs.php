<?php

namespace IBroStudio\PipedTasks\Concerns;

use Spatie\Activitylog\Models\Activity;

/**
 * @property \Illuminate\Database\Eloquent\Relations\MorphTo $processable
 */
trait HasLogs
{
    public function ensureLogPerformedOn(): void
    {
        // @phpstan-ignore-next-line
        if (! is_null($this->processable)) {
            Activity::where('batch_uuid', $this->log_batch_uuid)
                ->where('subject_type', '<>', get_class($this->processable))
                ->update([
                    // @phpstan-ignore-next-line
                    'subject_id' => $this->processable->id,
                    'subject_type' => get_class($this->processable),
                ]);
        }
    }
}
