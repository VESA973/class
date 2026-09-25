<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pages legales editables (mentions legales, CGU, confidentialite, CGL) et historique de leurs versions.
     * Contenu initial : modeles pre-remplis (database/legal/*.html) avec variables {raison_sociale}...
     */
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('legal_page_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        $pages = [
            ['legal_notice', 'Mentions légales', 'mentions-legales'],
            ['terms', 'Conditions générales d’utilisation', 'cgu'],
            ['privacy', 'Politique de confidentialité', 'politique-de-confidentialite'],
            ['rental_terms', 'Conditions générales de location', 'conditions-generales-de-location'],
        ];

        foreach ($pages as $position => [$key, $title, $slug]) {
            $content = file_get_contents(database_path("legal/{$slug}.html"));
            $id = DB::table('legal_pages')->insertGetId([
                'key' => $key, 'title' => $title, 'slug' => $slug, 'content' => $content,
                'is_published' => true, 'position' => $position, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('legal_page_versions')->insert([
                'legal_page_id' => $id, 'title' => $title, 'content' => $content, 'note' => 'Modèle initial', 'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_page_versions');
        Schema::dropIfExists('legal_pages');
    }
};
