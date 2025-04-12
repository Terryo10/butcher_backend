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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('order_number')->unique();
            $table->string('status')->default('pending'); // pending, processing, completed, cancelled
            $table->string('payment_status')->default('pending'); // pending, paid, failed
            $table->string('payment_type'); // card, paypal, cash_on_delivery, ecocash
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_reference')->nullable(); // Added here in the correct position
            $table->foreignId('address_id')->constrained()->onDelete('restrict');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->string('coupon_code')->nullable();
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
