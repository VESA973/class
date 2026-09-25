<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Destination et nombre de passagers. Colonnes facultatives : les
     * reservations existantes (et l'ancien formulaire) n'en ont pas.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('destination')->nullable()->after('pickup_location');
            $table->unsignedTinyInteger('passengers')->nullable()->after('destination');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['destination', 'passengers']);
        });
    }
};
