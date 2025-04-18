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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('pricing_type')->default('fixed');
            $table->string('unit')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('min_quantity', 10, 2)->nullable();
            $table->decimal('max_quantity', 10, 2)->nullable();
            $table->decimal('increment', 10, 2)->nullable();
            $table->integer('stock');
            $table->string('image');
            $table->longText('description');
            $table->unsignedBigInteger('subcategory_id');
            $table->foreign('subcategory_id')->references('id')->on('subcategories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
