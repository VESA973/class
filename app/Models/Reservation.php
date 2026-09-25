<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'cancelled', 'completed'];

    protected $fillable = [
        'vehicle_id',
        'prestation_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'start_date',
        'end_date',
        'start_at',
        'end_at',
        'days',
        'pickup_location',
        'destination',
        'passengers',
        'service_type',
        'estimated_total',
        'status',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'days' => 'integer',
            'estimated_total' => 'integer',
            'passengers' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Les reservations creees sans heure (ancien formulaire) recoivent une
        // periode en jours entiers, pour rester visibles dans les controles de
        // disponibilite et le planning.
        static::saving(function (Reservation $reservation): void {
            if ($reservation->start_at || ! $reservation->start_date) {
                return;
            }

            $lastDay = $reservation->end_date
                ?? $reservation->start_date->copy()->addDays(max((int) $reservation->days, 1) - 1);

            $reservation->start_at = $reservation->start_date->copy()->startOfDay();
            $reservation->end_at = $lastDay->copy()->startOfDay()->addDay();
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class);
    }
}
