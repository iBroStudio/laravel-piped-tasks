<?php

use IBroStudio\PipedTasks\Enums\StatesEnum;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class
{
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('class');
            $table->string('payload');
            $table->string('state')->default(StatesEnum::PENDING);
            $table->timestamps();
            $table->timestamp('ended_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};
