<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Reglement recu sur une facture (acompte ou paiement partiel / total). */
class InvoicePayment extends Model
{
    public const METHODS = ['virement' => 'Virement', 'carte' => 'Carte bancaire', 'especes' => 'Espèces', 'cheque' => 'Chèque', 'autre' => 'Autre'];

    protected $fillable = ['amount', 'paid_at', 'method', 'note'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'date'];
    }
}
