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
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('whatsapp_connected')->default(false)->after('whatsapp_number');
            $table->string('whatsapp_phone', 25)->nullable()->after('whatsapp_connected');
            $table->string('whatsapp_name', 100)->nullable()->after('whatsapp_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_connected', 'whatsapp_phone', 'whatsapp_name']);
        });
    }
};
