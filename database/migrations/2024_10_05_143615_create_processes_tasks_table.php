<?php

use IBroStudio\PipedTasks\Enums\StatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class
{
    public function up(): void
    {
        Schema::create('processes_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Process::class);
            $table->string('class');
            $table->string('state')->default(StatesEnum::PENDING);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_processes');
    }
};
