<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('state')->unique();
            // Whole naira, matching product prices. Converted to kobo only at
            // the point of talking to Paystack.
            $table->unsignedInteger('fee');
            // Free text rather than a number of days: "2-3 business days" and
            // "Same day in Lekki" are both things the shop wants to promise.
            $table->string('delivery_period');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
