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
        Schema::table('generated_emails', function (Blueprint $table) {
            $table->json('generated_variants')->nullable()->after('full_prompt_sent');
            $table->string('generated_subject')->nullable()->change();
            $table->text('generated_body')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_emails', function (Blueprint $table) {
            $table->dropColumn('generated_variants');
            $table->string('generated_subject')->nullable(false)->change();
            $table->text('generated_body')->nullable(false)->change();
        });
    }
};
