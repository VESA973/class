<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $vehicles = [
            [
                'name' => 'Lamborghini Urus',
                'category' => 'SUV',
                'horsepower' => 641,
                'daily_price' => 1000,
                'image_url' => 'https://images.unsplash.com/photo-1617814076668-9f44d08b4727?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Ferrari SF90 Stradale',
                'category' => 'Supercar',
                'horsepower' => 769,
                'fuel_type' => 'Hybride',
                'daily_price' => 1750,
                'image_url' => 'https://images.unsplash.com/photo-1592198084033-aade902d1aae?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Porsche Panamera Turbo',
                'category' => 'Berline',
                'horsepower' => 563,
                'daily_price' => 450,
                'image_url' => 'https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Mercedes GLC 63s AMG',
                'category' => 'SUV',
                'horsepower' => 503,
                'daily_price' => 375,
                'image_url' => 'https://images.unsplash.com/photo-1617469767053-d3b523a0b982?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Rolls Royce Ghost',
                'category' => 'Chauffeur',
                'horsepower' => 563,
                'daily_price' => 1200,
                'with_chauffeur' => true,
                'image_url' => 'https://images.unsplash.com/photo-1631295868223-63265b40d9e4?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'name' => 'Lamborghini Aventador S',
                'category' => 'Supercar',
                'horsepower' => 730,
                'daily_price' => 1500,
                'image_url' => 'https://images.unsplash.com/photo-1621135802920-133df287f89c?auto=format&fit=crop&w=900&q=80',
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::updateOrCreate(
                ['name' => $vehicle['name']],
                array_merge([
                    'fuel_type' => 'Essence',
                    'transmission' => 'Auto',
                    'is_available' => true,
                    'with_chauffeur' => false,
                ], $vehicle),
            );
        }
    }
}
