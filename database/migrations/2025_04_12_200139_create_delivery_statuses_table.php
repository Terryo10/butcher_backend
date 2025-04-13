<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color')->default('#000000');
            $table->timestamps();
        });

        // Insert default statuses
        DB::table('delivery_statuses')->insert([
            ['name' => 'pending', 'description' => 'Order placed, waiting for driver assignment', 'color' => '#FFC107', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'assigned', 'description' => 'Driver assigned to delivery', 'color' => '#2196F3', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'picked_up', 'description' => 'Order picked up by driver', 'color' => '#FF9800', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'in_transit', 'description' => 'Order is being delivered', 'color' => '#03A9F4', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delivered', 'description' => 'Order successfully delivered', 'color' => '#4CAF50', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'failed', 'description' => 'Delivery attempt failed', 'color' => '#F44336', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'returned', 'description' => 'Order returned to seller', 'color' => '#795548', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'unassigned', 'description' => 'Driver unassigned from delivery', 'color' => '#9E9E9E', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_statuses');
    }
};
