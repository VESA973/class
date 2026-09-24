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

    public static function current(): self
    {
        return self::query()->firstOrCreate([]);
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
