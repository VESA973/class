<?php

namespace App\Models;

use App\Services\CustomerDirectory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Client (particulier ou professionnel) : fiche commune aux demandes du site et aux devis. */
class Customer extends Model
{
    use SoftDeletes;

    public const TYPES = ['particulier' => 'Particulier', 'professionnel' => 'Professionnel'];

    public const SOURCES = ['site' => 'Site (réservation)', 'admin' => 'Administration', 'devis' => 'Devis', 'import' => 'Reprise des anciennes demandes'];

    protected $fillable = [
        'type', 'first_name', 'last_name', 'company_name', 'siret', 'email', 'phone_mobile', 'phone_landline',
        'address', 'postal_code', 'city', 'country', 'birth_date', 'license_number', 'license_issued_at', 'notes', 'source',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'license_issued_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Email en minuscules et numeros normalises : comparaison fiable pour les doublons.
        static::saving(function (Customer $customer): void {
            $customer->email = $customer->email ? mb_strtolower(trim($customer->email)) : null;
            $customer->phone_mobile_key = CustomerDirectory::phoneKey($customer->phone_mobile);
            $customer->phone_landline_key = CustomerDirectory::phoneKey($customer->phone_landline);
        });
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->latest('id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->latest('id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id')->withTrashed();
    }

    /** « Prénom Nom ». */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    /** Nom affiche dans les listes : raison sociale et/ou prenom nom. */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;

        if ($this->company_name) {
            return $name ? $this->company_name.' ('.$name.')' : $this->company_name;
        }

        return $name !== '' ? $name : ($this->email ?? 'Client n°'.$this->id);
    }

    /** Telephone principal (portable, sinon fixe). */
    public function getPhoneAttribute(): ?string
    {
        return $this->phone_mobile ?: $this->phone_landline;
    }

    /** Adresse sur une ligne (devis). */
    public function getFullAddressAttribute(): string
    {
        return collect([$this->address, trim(($this->postal_code ?? '').' '.($this->city ?? '')), $this->country])
            ->map(fn ($part) => trim((string) $part))->filter()->implode(', ');
    }
}
