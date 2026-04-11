<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_emails', function (Blueprint $table) {
            $table->string('sending_status')->default('draft')->after('status');
            // draft, queued, sending, sent, failed
            $table->index('sending_status');
        });
    }

    public function down(): void
    {
        Schema::table('generated_emails', function (Blueprint $table) {
            $table->dropIndex(['sending_status']);
            $table->dropColumn('sending_status');
        });
    }
};
