<?php

namespace App\Services;

use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Redirections 301/302 : lecture en cache, normalisation des adresses, suivi des changements de slug. */
class RedirectService
{
    public const CACHE_KEY = 'seo.redirects';

    /** "/vehicules/Old-Slug/?a=1" -> "/vehicules/old-slug" (chemin seul, sans slash final). */
    public static function normalize(string $path): string
    {
        $path = parse_url(trim($path), PHP_URL_PATH) ?: '/';
        $path = '/'.trim(rawurldecode($path), '/');

        return mb_strtolower($path);
    }

    /** @return array<string, array{id: int, to: string, code: int}> */
    public static function map(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function (): array {
                if (! Schema::hasTable('redirects')) {
                    return [];
                }

                return Redirect::query()->get(['id', 'from_path', 'to_url', 'status_code'])
                    ->mapWithKeys(fn (Redirect $redirect) => [$redirect->from_path => ['id' => $redirect->id, 'to' => $redirect->to_url, 'code' => $redirect->status_code]])
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Une adresse change (slug de vehicule, page legale) : l'ancienne redirige vers la nouvelle,
     * les redirections existantes vers l'ancienne sont mises a jour (pas de chaine) et rien ne boucle.
     */
    public static function addressChanged(string $oldPath, string $newPath): void
    {
        $old = self::normalize($oldPath);
        $new = self::normalize($newPath);

        if ($old === $new) {
            return;
        }

        Redirect::query()->where('from_path', $new)->delete();
        Redirect::query()->where('to_url', $old)->get()->each->update(['to_url' => $new]);
        Redirect::query()->updateOrCreate(['from_path' => $old], ['to_url' => $new, 'status_code' => 301]);
    }
}
