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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['warming_account_id']);
            $table->dropColumn('warming_account_id');
            $table->json('warming_account_ids')->nullable()->after('generated_email_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('warming_account_ids');
            $table->foreignId('warming_account_id')->nullable()->constrained('warming_accounts')->onDelete('cascade')->after('generated_email_id');
        });
    }
};
