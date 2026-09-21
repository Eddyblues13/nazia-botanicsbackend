<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Delivery, captured at checkout from the zone the customer picks.
            // The fee and period are copied onto the order rather than joined,
            // so changing a zone's price later never rewrites past orders.
            $table->string('delivery_state')->nullable()->after('delivery_address');
            $table->unsignedInteger('delivery_fee')->default(0)->after('delivery_state');
            $table->string('delivery_period')->nullable()->after('delivery_fee');

            // What the customer actually owes: subtotal + delivery_fee.
            $table->unsignedInteger('total')->default(0)->after('subtotal');

            // Payment. `payment_reference` is what we send to Paystack and what
            // the webhook quotes back, so it is indexed and unique.
            $table->string('payment_status')->default('unpaid')->index()->after('total');
            $table->string('payment_reference')->nullable()->unique()->after('payment_status');
            $table->string('payment_channel')->nullable()->after('payment_reference');
            $table->unsignedInteger('amount_paid')->default(0)->after('payment_channel');
            $table->timestamp('paid_at')->nullable()->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_state', 'delivery_fee', 'delivery_period', 'total',
                'payment_status', 'payment_reference', 'payment_channel',
                'amount_paid', 'paid_at',
            ]);
        });
    }
};
