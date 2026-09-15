<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('tag');
            $table->string('title');
            $table->text('excerpt');
            $table->unsignedSmallInteger('minutes')->default(4);
            // Drives the card treatment on the journal index: sage | terracotta | clay.
            $table->string('tone')->default('sage');
            $table->string('cta')->nullable();
            // Ordered blocks: { type: p|h|ul|ol, text?, items? }.
            $table->json('body');
            // The "read this next" hand-off: { id, teaser }.
            $table->json('next_up')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
