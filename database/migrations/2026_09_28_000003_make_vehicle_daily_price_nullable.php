<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Prix facultatif : un vehicule sans prix n'affiche aucun tarif sur le site (« sur devis »). Aucune donnee modifiee. */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('daily_price')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('daily_price')->nullable(false)->default(0)->change();
        });
    }
};
