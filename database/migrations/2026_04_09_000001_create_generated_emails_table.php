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
        Schema::create('generated_emails', function (Blueprint $table) {
            $table->id();
            $table->string('target_domain');
            $table->json('target_emails');
            $table->text('user_instructions');
            $table->string('product_service')->nullable();
            $table->string('tone')->default('professional');
            $table->text('system_prompt');
            $table->text('full_prompt_sent');
            $table->string('generated_subject');
            $table->text('generated_body');
            $table->string('gemini_model')->default('gemini-2.0-flash');
            $table->integer('tokens_used')->nullable();
            $table->float('generation_time_ms')->nullable();
            $table->string('status')->default('generated'); // generated, sent, tested
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('target_domain');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_emails');
    }
};
