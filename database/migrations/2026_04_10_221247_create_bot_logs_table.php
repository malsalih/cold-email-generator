<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('log_id')->nullable();
            $table->string('event'); // started, job_picked, composing, fields_filled, waiting_user, sent, failed, idle, stopped, error
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->string('session_id')->nullable(); // Groups logs by bot session
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_logs');
    }
};
