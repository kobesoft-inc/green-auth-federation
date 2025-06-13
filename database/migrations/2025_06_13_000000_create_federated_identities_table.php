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
        Schema::create('federated_identities', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship to support different user tables (users, admin_users, etc.)
            $table->morphs('authenticatable');
            
            // Federation provider information
            $table->string('driver')->index(); // e.g., 'google', 'microsoft', 'okta'
            $table->string('provider_user_id')->index(); // User ID from the provider
            
            // Tokens
            $table->text('access_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->text('refresh_token')->nullable();
            
            // Avatar tracking
            $table->string('avatar_hash', 64)->nullable()->index(); // SHA256 hash of avatar URL/data
            
            // Provider-specific data (JSON column for flexibility)
            $table->json('provider_data')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Composite unique index to ensure one provider account per user
            $table->unique(['authenticatable_type', 'authenticatable_id', 'driver', 'provider_user_id'], 'unique_federated_identity');
            
            // Index for efficient lookups
            $table->index(['driver', 'provider_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federated_identities');
    }
};