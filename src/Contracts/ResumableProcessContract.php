<?php

namespace IBroStudio\PipedTasks\Contracts;

use Illuminate\Foundation\Bus\PendingDispatch;

interface ResumableProcessContract
{
    public function resumeUrl(): string;

    public static function resume(int $process_id, ?Payload $payload = null): ?PendingDispatch;
}
