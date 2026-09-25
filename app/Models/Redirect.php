<?php

namespace App\Models;

use App\Services\RedirectService;
use Illuminate\Database\Eloquent\Model;

/** Redirection d'une ancienne adresse vers une nouvelle (Admin > SEO > Redirections). */
class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_url', 'status_code', 'hits', 'last_hit_at'];

    protected function casts(): array
    {
        return ['last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // La table des redirections est gardee en cache : on la vide a chaque changement.
        static::saved(fn () => RedirectService::flush());
        static::deleted(fn () => RedirectService::flush());
    }
}
