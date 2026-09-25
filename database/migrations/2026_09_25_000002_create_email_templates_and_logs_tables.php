<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modeles d'emails editables depuis l'admin + historique des envois.
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('template_key')->nullable()->index();
            $table->string('recipient');
            $table->string('subject');
            $table->string('status', 20)->index(); // queued | sent | failed
            $table->text('error')->nullable();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        $now = now();
        DB::table('email_templates')->insert([
            [
                'key' => 'reservation_received_customer',
                'name' => 'Accusé de réception de demande',
                'description' => 'Envoyé au client juste après sa demande de réservation sur le site.',
                'subject' => 'Votre demande de réservation n°{numero_reservation} - {site_nom}',
                'body' => "# Merci {nom_client} !\n\nNous avons bien reçu votre demande de réservation. Notre équipe vous recontacte rapidement pour la **confirmer**.\n\n- **Référence :** n°{numero_reservation}\n- **Véhicule :** {vehicule}\n- **Départ :** {date_depart}, {lieu_depart}\n- **Destination :** {destination}\n- **Retour :** {date_retour}\n- **Passagers :** {passagers}\n- **Estimation :** {montant_estime}\n\nCette demande ne vaut pas confirmation : le véhicule vous est réservé une fois la réservation confirmée par notre équipe.\n\nUne question ? Appelez-nous au **{site_telephone}** ou répondez simplement à cet email.\n\nÀ très bientôt,\nL'équipe {site_nom}",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'reservation_admin_notification',
                'name' => 'Notification admin : nouvelle demande',
                'description' => "Envoyé à l'adresse administrateur à chaque nouvelle demande de réservation.",
                'subject' => 'Nouvelle demande n°{numero_reservation} - {vehicule} - {date_depart}',
                'body' => "# Nouvelle demande de réservation\n\nUne demande vient d'être envoyée depuis le site. Elle est **en attente** de confirmation.\n\n- **Référence :** n°{numero_reservation}\n- **Véhicule :** {vehicule}\n- **Départ :** {date_depart}, {lieu_depart}\n- **Destination :** {destination}\n- **Retour :** {date_retour}\n- **Passagers :** {passagers}\n- **Estimation :** {montant_estime}\n\n## Client\n\n- **Nom :** {nom_client}\n- **Email :** {email_client}\n- **Téléphone :** {telephone_client}\n\n**Message :** {message_client}\n\n[Ouvrir la demande dans l'administration]({lien_admin})\n\nRépondez directement à cet email pour écrire au client.",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'quote_sent',
                'name' => 'Envoi de devis',
                'description' => 'Envoyé au client avec le devis en pièce jointe (module Devis).',
                'subject' => 'Votre devis {numero_devis} - {site_nom}',
                'body' => "# Bonjour {nom_client},\n\nSuite à votre demande de réservation n°{numero_reservation}, veuillez trouver ci-joint notre devis **{numero_devis}**.\n\n- **Véhicule :** {vehicule}\n- **Départ :** {date_depart}, {lieu_depart}\n- **Destination :** {destination}\n- **Montant :** {montant_devis}\n- **Valable jusqu'au :** {date_validite}\n\nPour l'accepter, il vous suffit de répondre à cet email ou de nous appeler au **{site_telephone}**.\n\nBien cordialement,\nL'équipe {site_nom}",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
    }
};
