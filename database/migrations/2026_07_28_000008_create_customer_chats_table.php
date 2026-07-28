<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('widget_key')->unique()->nullable()->after('slug');
        });

        Schema::create('customer_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('session_token')->unique();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('customer_chat_sessions')->onDelete('cascade');
            $table->string('sender'); // visitor, ai
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_chat_messages');
        Schema::dropIfExists('customer_chat_sessions');
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('widget_key');
        });
    }
};
