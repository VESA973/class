<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'cancelled', 'completed'];

    /** Suivi commercial de la demande (independant du statut de reservation). */
    public const REQUEST_STATUSES = [
        'new' => 'Nouvelle',
        'in_progress' => 'En cours',
        'quote_sent' => 'Devis envoyé',
        'accepted' => 'Accepté',
        'refused' => 'Refusé',
        'archived' => 'Archivée',
    ];

    /** Libelles affiches dans l'admin. */
    public const STATUS_LABELS = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'cancelled' => 'Annulée',
        'completed' => 'Terminée',
    ];

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
        'request_status',
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

    public function getRequestStatusLabelAttribute(): string
    {
        return self::REQUEST_STATUSES[$this->request_status ?? 'new'] ?? (string) $this->request_status;
    }

    public function quotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quote::class)->latest('id');
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReservationEvent::class)->latest('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
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
