<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained('shops')->onDelete('cascade');
            $table->boolean('show_qr')->default(true);
            $table->string('qr_url_source', 30)->default('web'); // web, facebook, instagram, twitter, pinterest, video_channel
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_signature')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_receipt_settings');
    }
};
