<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reverse_warming_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reverse_warming_account_id')->constrained()->onDelete('cascade');
            $table->string('target_email'); // Who received this
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->default('sent'); // sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reverse_warming_logs');
    }
};
