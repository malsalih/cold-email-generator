<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warming_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('warming_settings')->insert([
            ['key' => 'send_mode', 'value' => 'auto', 'created_at' => now(), 'updated_at' => now()],
            // auto = fully automatic
            // manual_send = bot fills, user clicks send
            // full_manual = bot opens compose only
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('warming_settings');
    }
};
