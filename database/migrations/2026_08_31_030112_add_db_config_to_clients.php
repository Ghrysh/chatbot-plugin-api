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
            $table->boolean('db_allow_read')->default(false);
            $table->string('db_host')->nullable();
            $table->string('db_port')->nullable();
            $table->string('db_database')->nullable();
            $table->string('db_username')->nullable();
            $table->string('db_password')->nullable();
            $table->json('db_allowed_tables')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'db_allow_read',
                'db_host',
                'db_port',
                'db_database',
                'db_username',
                'db_password',
                'db_allowed_tables'
            ]);
        });
    }
};
