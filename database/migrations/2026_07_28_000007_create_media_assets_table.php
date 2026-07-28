<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('file_url');
            $table->string('type')->default('video'); // video, image
            $table->string('category')->nullable(); // b_roll, product_demo, testimonial, screenshot
            $table->json('tags')->nullable(); // ["tiktok", "reels", "cod", "qris"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
