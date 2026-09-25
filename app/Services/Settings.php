<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Parametres globaux du site, lus une seule fois puis gardes en cache
 * (une requete SQL au maximum, puis plus aucune tant qu'ils ne changent pas).
 *
 *   app(Settings::class)->get('maintenance.enabled', false);
 *   app(Settings::class)->set(['maintenance.enabled' => true]);
 */
class Settings
{
    public const CACHE_KEY = 'site.settings';

    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            return $this->loaded = Cache::rememberForever(self::CACHE_KEY, function (): array {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            // Base indisponible (installation, migration en cours) : valeurs par defaut.
            return $this->loaded = [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @param array<string, mixed> $values */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function forget(string ...$keys): void
    {
        Setting::query()->whereIn('key', $keys)->delete();
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }
}
