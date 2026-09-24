<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute une periode precise (date + heure) aux reservations, sans toucher
     * aux colonnes existantes start_date / end_date / days.
     *
     * La periode est semi-ouverte [start_at, end_at[ : une reservation qui se
     * termine a 10:00 ne chevauche pas une reservation qui commence a 10:00.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dateTime('start_at')->nullable()->after('end_date');
            $table->dateTime('end_at')->nullable()->after('start_at');
            $table->index(['vehicle_id', 'start_at', 'end_at'], 'reservations_vehicle_period_index');
        });

        // Reservations existantes (jours entiers) : du premier jour 00:00
        // au lendemain du dernier jour 00:00.
        DB::table('reservations')
            ->whereNull('start_at')
            ->orderBy('id')
            ->each(function (object $reservation): void {
                $start = Carbon::parse($reservation->start_date)->startOfDay();
                $lastDay = $reservation->end_date
                    ? Carbon::parse($reservation->end_date)->startOfDay()
                    : $start->copy()->addDays(max((int) $reservation->days, 1) - 1);

                DB::table('reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'start_at' => $start,
                        'end_at' => $lastDay->copy()->addDay(),
                    ]);
            });
    }

    public function down(): void
    {
        // MariaDB/InnoDB supprime l'index automatique de la cle etrangere
        // vehicle_id des qu'un autre index commence par vehicle_id : on le
        // recree avant de retirer le notre.
        Schema::table('reservations', function (Blueprint $table): void {
            $table->index('vehicle_id', 'reservations_vehicle_id_foreign');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex('reservations_vehicle_period_index');
            $table->dropColumn(['start_at', 'end_at']);
        });
    }
};
