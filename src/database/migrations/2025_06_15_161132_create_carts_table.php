<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable(); // Para usuarios no registrados
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // Precio al momento de agregar al carrito
            $table->json('product_options')->nullable(); // Color, talla, etc.
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index('product_id');
            
            // Un usuario no puede tener el mismo producto duplicado en el carrito
            $table->unique(['user_id', 'product_id', 'product_options'], 'cart_user_product_unique');
            $table->unique(['session_id', 'product_id', 'product_options'], 'cart_session_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};