<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historique des emails envoyes (Admin > Emails > Historique). */
class EmailLog extends Model
{
    public const STATUS_LABELS = ['queued' => 'En file d’attente', 'sent' => 'Envoyé', 'failed' => 'Échec'];

    protected $fillable = ['template_key', 'recipient', 'subject', 'status', 'error', 'reservation_id', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
