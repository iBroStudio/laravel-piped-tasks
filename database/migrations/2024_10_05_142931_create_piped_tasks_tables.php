<?php

use IBroStudio\PipedTasks\Enums\ProcessStatesEnum;
use IBroStudio\PipedTasks\Models\Process;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('class');
            $table->longText('payload');
            $table->string('state')->default(ProcessStatesEnum::PENDING);
            $table->nullableMorphs('processable');
            $table->unsignedBigInteger('parent_process_id')->nullable();
            $table->string('log_batch_uuid')->nullable();
            $table->timestamps();
            $table->foreign('parent_process_id')->references('id')->on('processes');
        });

        Schema::create('processes_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Process::class);
            $table->string('class');
            $table->string('state')->default(ProcessStatesEnum::PENDING);
        });
    }

    public function down()
    {
        Schema::dropIfExists('processes_tasks');
        Schema::dropIfExists('processes');
    }
};
