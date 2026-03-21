<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdf_phrases', function (Blueprint $table) {
            $table->string('link_url', 500)->default('https://j2biznes.com')->after('phrase');
        });
    }

    public function down(): void
    {
        Schema::table('pdf_phrases', function (Blueprint $table) {
            $table->dropColumn('link_url');
        });
    }
};
