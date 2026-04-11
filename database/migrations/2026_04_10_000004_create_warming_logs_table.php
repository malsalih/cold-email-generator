<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warming_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warming_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('warming_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_email_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email');
            $table->string('subject_sent');
            $table->text('body_sent');
            $table->string('status')->default('pending'); // pending, sent, failed, bounced
            $table->text('error_message')->nullable();
            $table->string('source_type')->default('warming'); // warming, campaign
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('warming_account_id');
            $table->index('status');
            $table->index('source_type');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warming_logs');
    }
};
