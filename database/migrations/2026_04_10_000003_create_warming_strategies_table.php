<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warming_strategies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('schedule');
            // JSON array: [{"from_day":1,"to_day":3,"daily_sends":2}, {"from_day":4,"to_day":7,"daily_sends":5}, ...]
            $table->integer('min_delay_minutes')->default(3);
            $table->integer('max_delay_minutes')->default(15);
            $table->string('active_hours_start')->default('08:00');
            $table->string('active_hours_end')->default('20:00');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warming_strategies');
    }
};
