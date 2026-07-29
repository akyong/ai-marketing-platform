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
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->index(['workspace_id', 'type', 'created_at'], 'idx_ai_generations_workspace_type_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropIndex('idx_ai_generations_workspace_type_created');
        });
    }
};
