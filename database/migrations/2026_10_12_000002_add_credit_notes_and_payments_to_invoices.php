<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avoirs (numerotes AAAAAMM-NN, rattaches a la facture annulee) et paiements (acomptes, reglements partiels).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('type', 20)->default('invoice')->after('id')->index(); // invoice | credit_note
            $table->foreignId('credited_invoice_id')->nullable()->after('quote_id')->constrained('invoices')->nullOnDelete();
        });

        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('method', 30)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // Meme modele d'email pour les factures et les avoirs (modele pas encore personnalise : remplace).
        DB::table('email_templates')->where('key', 'invoice_sent')->where('subject', 'Votre facture {numero_facture} - {site_nom}')->update([
            'subject' => 'Votre {type_document} {numero_facture} - {site_nom}',
            'body' => "# Bonjour {nom_client},\n\nVeuillez trouver ci-joint notre {type_document} **{numero_facture}**.\n\n- **Montant :** {montant_facture}\n- **Reste à régler :** {reste_a_payer}\n- **Échéance :** {date_echeance}\n\nPour toute question, répondez simplement à cet email ou appelez-nous au **{site_telephone}**.\n\nBien cordialement,\nL'équipe {site_nom}",
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('credited_invoice_id');
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
