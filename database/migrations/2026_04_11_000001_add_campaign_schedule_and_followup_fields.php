<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Smart scheduling
            $table->string('send_start_time')->default('09:00')->after('scheduled_at');
            $table->string('send_end_time')->default('17:00')->after('send_start_time');
            $table->integer('min_delay_minutes')->default(5)->after('send_end_time');
            $table->integer('max_delay_minutes')->default(10)->after('min_delay_minutes');
            $table->date('send_start_date')->nullable()->after('max_delay_minutes');
            $table->string('timezone')->default('Asia/Riyadh')->after('send_start_date');

            // Follow-up chain
            $table->unsignedBigInteger('parent_campaign_id')->nullable()->after('timezone');
            $table->tinyInteger('followup_number')->default(0)->after('parent_campaign_id');
            $table->integer('followup_wait_days')->default(3)->after('followup_number');
            $table->integer('max_followups')->default(3)->after('followup_wait_days');
            $table->boolean('auto_followup')->default(false)->after('max_followups');
        });

        Schema::table('warming_logs', function (Blueprint $table) {
            $table->boolean('is_followup')->default(false)->after('source_type');
            $table->tinyInteger('followup_number')->default(0)->after('is_followup');
            $table->timestamp('schedule_send_at')->nullable()->after('followup_number');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'send_start_time', 'send_end_time',
                'min_delay_minutes', 'max_delay_minutes',
                'send_start_date', 'timezone',
                'parent_campaign_id', 'followup_number',
                'followup_wait_days', 'max_followups', 'auto_followup',
            ]);
        });

        Schema::table('warming_logs', function (Blueprint $table) {
            $table->dropColumn(['is_followup', 'followup_number', 'schedule_send_at']);
        });
    }
};
