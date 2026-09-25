<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Fiche vehicule publique : adresse lisible (slug), nombre de places,
     * modele 3D (.glb) et video 3D facultative.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->unsignedTinyInteger('seats')->nullable()->after('transmission');
            $table->string('model_path')->nullable()->after('image_url');
            $table->string('video_url', 500)->nullable()->after('model_path');
        });

        // Slug pour les vehicules existants ("Lamborghini Urus" -> "lamborghini-urus").
        $used = [];
        foreach (DB::table('vehicles')->orderBy('id')->get(['id', 'name']) as $vehicle) {
            $base = Str::slug($vehicle->name) ?: 'vehicule';
            $slug = $base;
            for ($i = 2; in_array($slug, $used, true); $i++) {
                $slug = "{$base}-{$i}";
            }
            $used[] = $slug;

            DB::table('vehicles')->where('id', $vehicle->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'seats', 'model_path', 'video_url']);
        });
    }
};
