<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('requires_delivery')->default(true)->after('tracking_number');
            $table->timestamp('delivery_deadline')->nullable()->after('requires_delivery');
            $table->string('delivery_priority')->default('normal')->after('delivery_deadline'); // high, normal, low
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['requires_delivery', 'delivery_deadline', 'delivery_priority']);
        });
    }
};
