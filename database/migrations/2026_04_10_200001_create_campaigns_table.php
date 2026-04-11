<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('generated_email_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warming_account_id')->constrained()->cascadeOnDelete();
            $table->string('custom_subject')->nullable();
            $table->longText('custom_body')->nullable();
            $table->json('recipients'); // array of emails
            $table->integer('delay_minutes')->default(5);
            $table->timestamp('scheduled_at')->nullable(); // null = start now
            $table->enum('status', ['draft', 'scheduled', 'running', 'paused', 'completed', 'failed'])->default('draft');
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
