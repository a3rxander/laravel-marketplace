<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            
            // Calificación y contenido
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->json('images')->nullable(); // Fotos del producto por el cliente
            
            // Verificación de compra
            $table->boolean('verified_purchase')->default(false);
            
            // Estado de moderación
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users');
            $table->timestamp('moderated_at')->nullable();
            
            // Métricas de utilidad
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            
            // Respuesta del vendedor
            $table->text('seller_response')->nullable();
            $table->timestamp('seller_responded_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['product_id', 'status', 'rating']);
            $table->index(['seller_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['verified_purchase', 'status']);
            
            // Un usuario solo puede hacer una review por producto comprado
            $table->unique(['user_id', 'product_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};