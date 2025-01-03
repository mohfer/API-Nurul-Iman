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
        Schema::create('news_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('news_id')->references('id')->on('news')->onDelete('cascade');
            $table->foreignUuid('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->timestamps();

            $table->index(['news_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_tags');
    }
};
