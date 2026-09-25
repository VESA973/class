<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'horsepower',
        'fuel_type',
        'transmission',
        'seats',
        'daily_price',
        'image_path',
        'image_url',
        'model_path',
        'video_url',
        'description',
        'is_available',
        'with_chauffeur',
    ];

    protected function casts(): array
    {
        return [
            'daily_price' => 'integer',
            'horsepower' => 'integer',
            'seats' => 'integer',
            'is_available' => 'boolean',
            'with_chauffeur' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Adresse publique /vehicules/{slug} generee a partir du nom, unique.
        static::saving(function (Vehicle $vehicle): void {
            if ($vehicle->slug) {
                return;
            }

            $base = Str::slug($vehicle->name) ?: 'vehicule';
            $slug = $base;

            for ($i = 2; static::query()->where('slug', $slug)->whereKeyNot($vehicle->getKey())->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }

            $vehicle->slug = $slug;
        });

        // Adresse publique modifiee : l'ancienne redirige automatiquement (301) vers la nouvelle.
        static::updated(function (Vehicle $vehicle): void {
            $old = $vehicle->getRawOriginal('slug');

            if ($vehicle->wasChanged('slug') && $old) {
                \App\Services\RedirectService::addressChanged('/vehicules/'.$old, '/vehicules/'.$vehicle->slug);
            }
        });
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function getDisplayImageAttribute(): string
    {
        if ($this->image_path) {
            return Storage::url($this->image_path);
        }

        return $this->image_url ?: 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=1200&q=80';
    }

    /** Version WebP de la photo envoyee (null pour une image externe ou non optimisee). */
    public function getDisplayImageWebpAttribute(): ?string
    {
        return \App\Services\ImageOptimizer::webpUrl($this->image_path);
    }

    /** URL publique du modele 3D (.glb), ou null. */
    public function getModelUrlAttribute(): ?string
    {
        return $this->model_path ? Storage::url($this->model_path) : null;
    }
}
