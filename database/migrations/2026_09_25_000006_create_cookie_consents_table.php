<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registre des consentements cookies (preuve CNIL) : identifiant aleatoire du navigateur,
     * choix par categorie, version de la politique, date. Aucune adresse IP ni navigateur enregistres.
     */
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('consent_id')->index();
            $table->unsignedInteger('policy_version');
            $table->string('action', 20); // accept_all | reject_all | custom
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
