<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $fillable = [
        'logo_path',
        'logo_width',
        'hero_image_path',
    ];

    protected function casts(): array
    {
        return [
            'logo_width' => 'integer',
        ];
    }

    public const CACHE_KEY = 'site.site_setting.attributes';

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget(self::CACHE_KEY));
    }

    /** Lu sur chaque page publique : garde en cache, vide automatiquement a chaque modification. */
    public static function current(): self
    {
        // Le cache ne stocke que des valeurs simples (Laravel refuse de relire des objets PHP) :
        // on garde les colonnes et on reconstruit le modele.
        $attributes = \Illuminate\Support\Facades\Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->firstOrCreate([])->getAttributes());

        return (new self)->newFromBuilder($attributes);
    }

    public function getHeroImageWebpUrlAttribute(): ?string
    {
        return \App\Services\ImageOptimizer::webpUrl($this->hero_image_path);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::url($this->logo_path);
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        if (! $this->hero_image_path) {
            return null;
        }

        return Storage::url($this->hero_image_path);
    }
}
