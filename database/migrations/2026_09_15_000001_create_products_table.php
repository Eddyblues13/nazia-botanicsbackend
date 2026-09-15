<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline');
            // Whole naira — the storefront prices have no kobo component. This
            // is the "from" price; each size carries its own price.
            $table->unsignedInteger('price_from');
            $table->text('description')->nullable();
            // [{ label: "2 oz", price: 15000 }] — the variants a visitor picks from.
            $table->json('sizes');
            // [{ icon: "sprout", title: "...", detail: "..." }] — spotlight badges.
            $table->json('highlights');
            // [{ name: "Rosemary", role: "The Stimulator", detail: "..." }]
            $table->json('ingredients');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
