<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Type de prestation choisi par le client (texte copie : reste lisible si le type est retire de la liste). */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('prestation_type', 120)->nullable()->after('service_type');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('prestation_type');
        });
    }
};
