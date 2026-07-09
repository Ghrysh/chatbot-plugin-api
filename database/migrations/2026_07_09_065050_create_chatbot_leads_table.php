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
        Schema::create('chatbot_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('session_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            
            // New fields from ScanYuk port
            $table->string('ip_address')->nullable();
            $table->string('topic_context')->nullable();
            $table->string('contact_info')->nullable();
            $table->longText('chat_history')->nullable();
            $table->text('last_message')->nullable();
            $table->enum('live_chat_status', ['none', 'pending', 'active', 'ended'])->default('none');
            $table->unsignedBigInteger('admin_id')->nullable(); // The user ID of the admin handling the chat

            // Kept for legacy plugin compatibility
            $table->string('status')->default('pending');

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_leads');
    }
};
