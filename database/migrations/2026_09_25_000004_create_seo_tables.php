<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SEO : balises par page (cle "page:home"...) ou par element (vehicule, page legale),
     * et redirections 301/302 (utiles quand une adresse change).
     */
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table): void {
            $table->id();
            $table->string('page_key')->nullable()->unique();
            $table->nullableMorphs('seoable');
            $table->string('title')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id']);
        });

        Schema::create('redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('from_path', 500)->unique();
            $table->string('to_url', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('seo_metas');
    }
};
