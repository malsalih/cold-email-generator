<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warming_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('personal');
            // personal, business_intro, follow_up, newsletter, friendly, thank_you
            $table->string('subject');
            $table->text('body');
            $table->json('variables')->nullable();
            // e.g. ["{name}", "{company}", "{date}"]
            $table->boolean('is_active')->default(true);
            $table->integer('times_used')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warming_templates');
    }
};
