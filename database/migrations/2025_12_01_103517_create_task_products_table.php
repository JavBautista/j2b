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
        Schema::create('task_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('qty_delivered')->default(1);
            $table->integer('qty_used')->default(0);
            $table->integer('qty_returned')->default(0);
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('delivered_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['task_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_products');
    }
};
