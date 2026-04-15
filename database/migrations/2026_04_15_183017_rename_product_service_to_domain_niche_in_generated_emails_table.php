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
            $table->renameColumn('product_service', 'domain_niche');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_emails', function (Blueprint $table) {
            $table->renameColumn('domain_niche', 'product_service');
        });
    }
};
