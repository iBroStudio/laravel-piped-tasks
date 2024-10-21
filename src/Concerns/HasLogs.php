<?php

namespace IBroStudio\PipedTasks\Concerns;

use Spatie\Activitylog\Models\Activity;

trait HasLogs
{
    public function ensureLogPerformedOn(): void
    {
        if (! is_null($this->processable)) {
            Activity::where('batch_uuid', $this->log_batch_uuid)
                ->where('subject_type', '<>', get_class($this->processable))
                ->update([
                    'subject_id' => $this->processable->id,
                    'subject_type' => get_class($this->processable),
                ]);
        }
    }
}
