<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_consignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('rent_id')->constrained('rents')->onDelete('cascade');

            $table->integer('folio')->default(0);
            $table->date('delivery_date');
            $table->text('notes')->nullable();

            $table->string('pdf_path')->nullable();

            $table->dateTime('signed_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('received_by_name')->nullable();

            $table->string('status', 20)->default('vigente');
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            $table->unique(['shop_id', 'folio']);
            $table->index(['rent_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_consignments');
    }
};
