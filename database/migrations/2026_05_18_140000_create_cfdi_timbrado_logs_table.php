<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_timbrado_logs', function (Blueprint $t) {
            $t->bigIncrements('id');

            $t->unsignedBigInteger('shop_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('cfdi_invoice_id')->nullable();
            $t->unsignedBigInteger('receipt_id')->nullable();

            $t->string('request_id', 32);
            $t->enum('source', ['plataforma', 'ionic', 'system', 'unknown'])->default('unknown');
            $t->string('event_type', 64);
            $t->enum('pipeline', ['json', 'xml_compat'])->default('json');
            $t->enum('status', ['success', 'error', 'warning', 'pending']);

            $t->unsignedSmallInteger('http_status')->nullable();
            $t->char('uuid', 36)->nullable();
            $t->unsignedInteger('duration_ms')->nullable();

            $t->longText('request_payload')->nullable();   // JSON sanitizado; "gzip:..." si comprimido
            $t->longText('response_payload')->nullable();  // idem

            $t->string('error_code', 64)->nullable();
            $t->text('error_message')->nullable();

            $t->json('attempts')->nullable();
            $t->json('metadata')->nullable();

            $t->timestamp('created_at')->useCurrent();

            $t->index(['shop_id', 'created_at'], 'idx_shop_created');
            $t->index('request_id', 'idx_request_id');
            $t->index('cfdi_invoice_id', 'idx_cfdi_invoice');
            $t->index(['status', 'event_type'], 'idx_status_event');
            $t->index('uuid', 'idx_uuid');

            $t->foreign('shop_id')->references('id')->on('shops')->nullOnDelete();
            $t->foreign('cfdi_invoice_id')->references('id')->on('cfdi_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_timbrado_logs');
    }
};
