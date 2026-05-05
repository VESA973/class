<?php

namespace Database\Seeders;

use App\Models\Prestation;
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

        $prestations = [
            [
                'name' => 'Mariages',
                'description' => 'Une arrivée élégante et un accompagnement discret pour les moments importants.',
                'image_url' => 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 10,
            ],
            [
                'name' => 'Transferts',
                'description' => 'Aéroports, gares, hôtels et trajets professionnels avec prise en charge soignée.',
                'image_url' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 20,
            ],
            [
                'name' => 'Soirées',
                'description' => 'Chauffeur privé pour vos sorties, dîners, événements VIP et retours en sérénité.',
                'image_url' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 30,
            ],
            [
                'name' => 'Évènements privés',
                'description' => 'Mise à disposition de véhicules avec chauffeur selon votre programme.',
                'image_url' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 40,
            ],
            [
                'name' => 'Voyages d’affaires',
                'description' => 'Déplacements ponctuels, rendez-vous multi-adresses et accueil de collaborateurs.',
                'image_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 50,
            ],
        ];

        foreach ($prestations as $prestation) {
            Prestation::updateOrCreate(
                ['name' => $prestation['name']],
                array_merge(['is_active' => true], $prestation),
            );
        }
    }
}
