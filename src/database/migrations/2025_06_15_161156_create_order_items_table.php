<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            
            // Información del producto al momento de la compra
            $table->string('product_name'); // Snapshot del nombre
            $table->string('product_sku'); // Snapshot del SKU
            $table->json('product_options')->nullable(); // Color, talla, etc.
            
            // Precios y cantidades
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Precio unitario al momento de compra
            $table->decimal('total_price', 10, 2); // quantity * unit_price
            
            // Comisiones del marketplace
            $table->decimal('commission_rate', 5, 2); // % de comisión
            $table->decimal('commission_amount', 10, 2); // Monto de comisión
            $table->decimal('seller_earnings', 10, 2); // Lo que recibe el vendedor
            
            // Estado del item (puede diferir del estado general de la orden)
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])->default('pending');
            
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['order_id', 'seller_id']);
            $table->index(['seller_id', 'status']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};