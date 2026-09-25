<?php

namespace App\Models;

use App\Services\RedirectService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/** Page legale editable (Admin > Pages legales). */
class LegalPage extends Model
{
    public const FOOTER_CACHE_KEY = 'legal.footer_links';

    protected $fillable = ['title', 'slug', 'content', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function (LegalPage $page): void {
            Cache::forget(self::FOOTER_CACHE_KEY);

            // Nouvelle adresse : l'ancienne redirige automatiquement (301).
            if ($page->wasChanged('slug') && $page->getRawOriginal('slug')) {
                RedirectService::addressChanged('/'.$page->getRawOriginal('slug'), '/'.$page->slug);
            }
        });
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LegalPageVersion::class)->latest('id');
    }

    /** Liens du pied de page (pages publiees), gardes en cache. @return list<array{title: string, url: string}> */
    public static function footerLinks(): array
    {
        try {
            return Cache::rememberForever(self::FOOTER_CACHE_KEY, fn () => static::query()
                ->where('is_published', true)->orderBy('position')->get(['title', 'slug'])
                ->map(fn (LegalPage $page) => ['title' => $page->title, 'url' => '/'.$page->slug])->all());
        } catch (\Throwable) {
            return [];
        }
    }

    public function getUrlAttribute(): string
    {
        return url('/'.$this->slug);
    }
}
