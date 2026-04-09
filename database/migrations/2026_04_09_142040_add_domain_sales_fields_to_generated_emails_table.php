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
            $table->string('owned_domain')->nullable()->after('id');
            $table->string('target_website')->nullable()->after('owned_domain');
            $table->string('target_domain')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_emails', function (Blueprint $table) {
            $table->dropColumn('owned_domain');
            $table->dropColumn('target_website');
            $table->string('target_domain')->nullable(false)->change();
        });
    }
};
