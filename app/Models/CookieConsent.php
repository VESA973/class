<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** Preuve de consentement cookies (sans donnee personnelle superflue). Supprimee apres 13 mois. */
class CookieConsent extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    public const ACTIONS = ['accept_all' => 'Tout accepté', 'reject_all' => 'Tout refusé', 'custom' => 'Personnalisé'];

    protected $fillable = ['consent_id', 'policy_version', 'action', 'analytics', 'marketing'];

    protected function casts(): array
    {
        return ['analytics' => 'boolean', 'marketing' => 'boolean', 'created_at' => 'datetime'];
    }

    /** Nettoyage automatique (php artisan model:prune, planifie chaque jour). */
    public function prunable()
    {
        return static::query()->where('created_at', '<', now()->subMonths(13));
    }
}
