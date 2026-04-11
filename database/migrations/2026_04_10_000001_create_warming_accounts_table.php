<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warming_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('display_name');
            $table->string('domain')->default('eagnt.com');
            $table->string('session_dir')->nullable();
            $table->boolean('is_logged_in')->default(false);
            $table->integer('daily_limit')->default(2);
            $table->integer('current_day_sent')->default(0);
            $table->integer('total_sent')->default(0);
            $table->integer('warming_day')->default(0);
            $table->date('warming_started_at')->nullable();
            $table->string('status')->default('pending'); // pending, active, paused, suspended
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_login_check')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warming_accounts');
    }
};
