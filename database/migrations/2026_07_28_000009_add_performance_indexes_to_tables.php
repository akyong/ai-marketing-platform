<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run performance indexing across all key database tables.
     */
    public function up(): void
    {
        // 1. Posts table indexes for publishing engine & cron scheduling
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['workspace_id', 'status'], 'idx_posts_workspace_status');
            $table->index(['status', 'scheduled_at'], 'idx_posts_status_scheduled');
        });

        // 2. AI Generations table index for history & dashboard queries
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->index(['workspace_id', 'created_at'], 'idx_ai_generations_workspace_created');
        });

        // 3. API Keys table index for smart key pool failover
        Schema::table('api_keys', function (Blueprint $table) {
            $table->index(['provider', 'is_active', 'error_count'], 'idx_api_keys_provider_active');
        });

        // 4. Media Assets table index for multi-format video/photo pipeline
        Schema::table('media_assets', function (Blueprint $table) {
            $table->index(['workspace_id', 'type'], 'idx_media_assets_workspace_type');
        });

        // 5. Customer Chat Messages index for CS Live Chat Widget
        Schema::table('customer_chat_messages', function (Blueprint $table) {
            $table->index(['session_id', 'created_at'], 'idx_chat_messages_session_created');
        });

        // 6. Workspaces table owner_id index
        Schema::table('workspaces', function (Blueprint $table) {
            $table->index('owner_id', 'idx_workspaces_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropIndex('idx_workspaces_owner');
        });

        Schema::table('customer_chat_messages', function (Blueprint $table) {
            $table->dropIndex('idx_chat_messages_session_created');
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropIndex('idx_media_assets_workspace_type');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex('idx_api_keys_provider_active');
        });

        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropIndex('idx_ai_generations_workspace_created');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_status_scheduled');
            $table->dropIndex('idx_posts_workspace_status');
        });
    }
};
