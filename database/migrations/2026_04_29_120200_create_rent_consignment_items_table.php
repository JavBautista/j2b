<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_consignment_id')->constrained('rent_consignments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');

            $table->integer('qty');
            $table->integer('qty_returned')->default(0);
            $table->string('description')->nullable();

            $table->timestamps();

            $table->index('rent_consignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_consignment_items');
    }
};
