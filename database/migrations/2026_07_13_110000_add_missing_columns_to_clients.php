<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'email')) {
                $table->string('email')->nullable()->after('name');
            }
            if (!Schema::hasColumn('clients', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Kita abaikan down untuk menghindari masalah rollback pada kolom shared
        });
    }
};
