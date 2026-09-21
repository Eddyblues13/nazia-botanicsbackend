<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery priced by area rather than by state.
     *
     * A state is too coarse to charge by: Lekki and Ikorodu are both Lagos but
     * are not the same trip. A zone is a named group of areas with one price,
     * which is how the delivery actually works.
     */
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->renameColumn('state', 'name');
        });

        Schema::table('delivery_zones', function (Blueprint $table) {
            // The areas this zone covers, so a customer can tell which one is
            // theirs. Held as a list rather than free text so the storefront
            // can search it.
            $table->json('areas')->nullable()->after('name');
            // Zones are shown in a deliberate order — cheapest or nearest
            // first — not alphabetically.
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('delivery_state', 'delivery_zone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('delivery_zone', 'delivery_state');
        });

        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn(['areas', 'sort_order']);
        });

        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->renameColumn('name', 'state');
        });
    }
};
