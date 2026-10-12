<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Facture FAAAAMM-NN (ex. F202610-03) ou avoir AAAAAMM-NN (type credit_note, annule credited_invoice).
 * Jamais modifiee ni supprimee une fois emise : une erreur se corrige par un avoir.
 */
class Invoice extends Model
{
    public const STATUS_LABELS = ['issued' => 'À régler', 'paid' => 'Payée', 'cancelled' => 'Annulée (avoir)', 'credit' => 'Avoir'];

    public const STATUS_CLASSES = ['issued' => 'tag-pending', 'paid' => 'tag-success', 'cancelled' => 'tag-danger', 'credit' => 'tag-muted'];

    protected $fillable = [
        'type', 'quote_id', 'credited_invoice_id', 'reservation_id', 'customer_id', 'number', 'year', 'sequence', 'status', 'customer_name', 'customer_email',
        'customer_phone', 'customer_address', 'subject', 'issued_at', 'due_at', 'discount_type', 'discount_value', 'vat_exempt',
        'subtotal_ht', 'discount_ht', 'total_ht', 'total_vat', 'total_ttc', 'notes', 'pdf_path', 'sent_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'date',
            'sent_at' => 'datetime',
            'discount_value' => 'decimal:2',
            'vat_exempt' => 'boolean',
            'subtotal_ht' => 'decimal:2',
            'discount_ht' => 'decimal:2',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('paid_at')->orderBy('id');
    }

    /** Facture annulee par cet avoir. */
    public function creditedInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'credited_invoice_id');
    }

    /** Avoir qui annule cette facture. */
    public function creditNote(): HasOne
    {
        return $this->hasOne(self::class, 'credited_invoice_id');
    }

    public function getIsCreditNoteAttribute(): bool
    {
        return $this->type === 'credit_note';
    }

    public function getDocumentLabelAttribute(): string
    {
        return $this->is_credit_note ? 'Avoir' : 'Facture';
    }

    public function getAmountPaidAttribute(): float
    {
        return round((float) $this->payments->sum('amount'), 2);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, round((float) $this->total_ttc - $this->amount_paid, 2));
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    public function getStatusClassAttribute(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'issued' && $this->amount_paid > 0) {
            return 'Partiellement payée';
        }

        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** En retard : a regler et echeance depassee. */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'issued' && $this->due_at->isBefore(now(config('app.local_timezone'))->startOfDay());
    }
}
