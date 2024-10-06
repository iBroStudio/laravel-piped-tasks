<?php

declare(strict_types=1);

use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/piped-tasks/process/{process_id}', function (Request $request, int $process_id) {
    Process::resume($process_id);

    return response()->json(['message' => 'ok']);
})
    ->name('piped-tasks-process')
    ->middleware('signed');
