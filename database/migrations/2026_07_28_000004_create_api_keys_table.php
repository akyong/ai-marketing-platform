<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('provider')->default('gemini'); // gemini, openai, claude, telegram
            $table->string('key_name');
            $table->text('api_key');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('quota_exceeded_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
