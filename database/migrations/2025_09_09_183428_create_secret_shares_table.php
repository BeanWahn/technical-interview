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
        Schema::create('secret_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique(); // Unique sharing token
            $table->foreignId('secret_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
            $table->text('encrypted_content'); // Re-encrypted content for sharing
            $table->string('sharing_key', 32); // Temporary key for this share
            $table->timestamp('expires_at'); // Expiration time
            $table->timestamp('accessed_at')->nullable(); // When it was accessed
            $table->string('accessed_ip')->nullable(); // IP address of access
            $table->boolean('is_used')->default(false); // One-time use flag
            $table->integer('max_access_count')->default(1); // Maximum access count
            $table->integer('access_count')->default(0); // Current access count
            $table->text('notes')->nullable(); // Optional notes for the recipient
            $table->timestamps();

            // Indexes for performance
            $table->index(['token', 'is_used', 'expires_at']);
            $table->index(['secret_id', 'shared_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secret_shares');
    }
};
