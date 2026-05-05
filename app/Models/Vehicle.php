<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'horsepower',
        'fuel_type',
        'transmission',
        'daily_price',
        'image_path',
        'image_url',
        'description',
        'is_available',
        'with_chauffeur',
    ];

    protected function casts(): array
    {
        return [
            'daily_price' => 'integer',
            'horsepower' => 'integer',
            'is_available' => 'boolean',
            'with_chauffeur' => 'boolean',
        ];
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
}
