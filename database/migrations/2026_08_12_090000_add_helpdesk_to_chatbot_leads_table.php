<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('helpdesk_id')->nullable()->after('admin_id');
            $table->string('helpdesk_name')->nullable()->after('helpdesk_id');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_leads', function (Blueprint $table) {
            $table->dropColumn(['helpdesk_id', 'helpdesk_name']);
        });
    }
};
