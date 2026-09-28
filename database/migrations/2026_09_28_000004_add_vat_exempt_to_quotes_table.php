<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Devis sans TVA (ex. Guyane, art. 294 du CGI) : memorise par devis, les anciens devis gardent leur TVA. */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('vat_exempt')->default(false)->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('vat_exempt');
        });
    }
};
