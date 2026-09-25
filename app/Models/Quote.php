<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Devis numerote DEV-AAAA-NNNN, cree a partir d'une demande (reservation). */
class Quote extends Model
{
    public const STATUS_LABELS = ['draft' => 'Brouillon', 'sent' => 'Envoyé', 'accepted' => 'Accepté', 'refused' => 'Refusé'];

    /** Classe CSS de l'etiquette de statut dans l'admin. */
    public const STATUS_CLASSES = ['draft' => 'tag-muted', 'sent' => 'tag-pending', 'accepted' => 'tag-success', 'refused' => 'tag-danger'];

    public const VAT_RATES = ['20' => '20 %', '10' => '10 %', '5.5' => '5,5 %', '2.1' => '2,1 %', '0' => '0 % (exonéré)'];

    protected $fillable = [
        'reservation_id', 'number', 'year', 'sequence', 'status', 'customer_name', 'customer_email', 'customer_phone',
        'customer_address', 'subject', 'issued_at', 'valid_until', 'discount_type', 'discount_value', 'subtotal_ht',
        'discount_ht', 'total_ht', 'total_vat', 'total_ttc', 'conditions', 'notes', 'pdf_path', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'discount_value' => 'decimal:2',
            'subtotal_ht' => 'decimal:2',
            'discount_ht' => 'decimal:2',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('position');
    }

    public function getStatusClassAttribute(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public static function money(float|string|null $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ').' €';
    }
}
