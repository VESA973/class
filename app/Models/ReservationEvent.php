<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historique d'une demande : creation, devis, envois, changements de statut. */
class ReservationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['reservation_id', 'user_id', 'type', 'description', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param array<string, mixed> $meta */
    public static function record(Reservation $reservation, string $type, string $description, array $meta = []): self
    {
        return static::create([
            'reservation_id' => $reservation->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => mb_substr($description, 0, 500),
            'meta' => $meta ?: null,
        ]);
    }
}
