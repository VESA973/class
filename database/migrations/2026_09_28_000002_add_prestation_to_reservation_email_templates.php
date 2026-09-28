<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ajoute la ligne « Prestation » aux 2 emails de reservation (sans toucher un modele deja personnalise sur ce point). */
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        foreach (['reservation_received_customer', 'reservation_admin_notification'] as $key) {
            $template = DB::table('email_templates')->where('key', $key)->first();

            if ($template && ! str_contains($template->body, '{type_prestation}') && str_contains($template->body, '- **Véhicule :** {vehicule}')) {
                DB::table('email_templates')->where('key', $key)->update([
                    'body' => str_replace('- **Véhicule :** {vehicule}', "- **Prestation :** {type_prestation}\n- **Véhicule :** {vehicule}", $template->body),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['reservation_received_customer', 'reservation_admin_notification'] as $key) {
            $template = DB::table('email_templates')->where('key', $key)->first();

            if ($template) {
                DB::table('email_templates')->where('key', $key)->update([
                    'body' => str_replace("- **Prestation :** {type_prestation}\n", '', $template->body),
                ]);
            }
        }
    }
};
