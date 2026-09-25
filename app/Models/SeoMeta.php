<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/** Balises SEO personnalisees d'une page (page_key) ou d'un element (vehicule, page legale). */
class SeoMeta extends Model
{
    protected $fillable = ['page_key', 'seoable_type', 'seoable_id', 'title', 'description', 'og_image_path', 'canonical_url', 'noindex'];

    protected function casts(): array
    {
        return ['noindex' => 'boolean'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->og_image_path ? Storage::disk('public')->url($this->og_image_path) : null;
    }
}
