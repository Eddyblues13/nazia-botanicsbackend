<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // What the stock in this line cost to make, snapshotted at the
            // moment of sale so reworking a recipe never rewrites past profit.
            //
            // Nullable on purpose: orders placed before a cost was recorded
            // have no honest figure, and reporting them as zero-cost would
            // overstate profit rather than admit the gap.
            $table->unsignedInteger('unit_cost')->nullable()->after('unit_price');
            $table->unsignedInteger('line_cost')->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'line_cost']);
        });
    }
};
