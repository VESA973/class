<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Factures numerotees FAAAAMM-NN, creees a partir d'un devis (copie figee des lignes et montants).
     * Une facture emise n'est ni modifiable ni supprimable (numerotation continue obligatoire).
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->string('status', 20)->default('issued')->index(); // issued | paid | cancelled
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('subject')->nullable();
            $table->date('issued_at');
            $table->date('due_at');
            $table->string('discount_type', 10)->default('none');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->boolean('vat_exempt')->default(false);
            $table->decimal('subtotal_ht', 12, 2)->default(0);
            $table->decimal('discount_ht', 12, 2)->default(0);
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->decimal('total_vat', 12, 2)->default(0);
            $table->decimal('total_ttc', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price_ht', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->timestamps();
        });

        DB::table('email_templates')->insert([
            'key' => 'invoice_sent',
            'name' => 'Envoi de facture',
            'description' => 'Envoyé au client avec la facture en pièce jointe (module Factures).',
            'subject' => 'Votre facture {numero_facture} - {site_nom}',
            'body' => "# Bonjour {nom_client},\n\nVeuillez trouver ci-joint notre facture **{numero_facture}**.\n\n- **Montant :** {montant_facture}\n- **À régler avant le :** {date_echeance}\n\nPour toute question, répondez simplement à cet email ou appelez-nous au **{site_telephone}**.\n\nBien cordialement,\nL'équipe {site_nom}",
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('key', 'invoice_sent')->delete();
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
