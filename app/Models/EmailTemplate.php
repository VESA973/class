<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modele d'email editable (Admin > Emails > Modeles).
 * Le corps est ecrit en Markdown simple ; les variables {nom_client}, {vehicule}... sont remplacees a l'envoi.
 */
class EmailTemplate extends Model
{
    protected $fillable = ['subject', 'body', 'is_active'];

    /** Variables communes aux emails lies a une reservation => description (affichee dans l'admin). */
    public const RESERVATION_VARIABLES = [
        'nom_client' => 'Nom du client',
        'email_client' => 'Email du client',
        'telephone_client' => 'Téléphone du client',
        'message_client' => 'Message laissé par le client',
        'numero_reservation' => 'Numéro de la réservation',
        'vehicule' => 'Véhicule réservé',
        'date_depart' => 'Date et heure de départ',
        'date_retour' => 'Date et heure de retour',
        'lieu_depart' => 'Lieu de départ',
        'destination' => 'Destination',
        'passagers' => 'Nombre de passagers',
        'montant_estime' => 'Estimation du prix',
        'lien_admin' => 'Lien vers la réservation dans l’admin',
    ];

    public const QUOTE_VARIABLES = [
        'numero_devis' => 'Numéro du devis (ex. DEV-2026-0001)',
        'montant_devis' => 'Montant TTC du devis',
        'date_validite' => 'Date de validité du devis',
    ];

    public const SITE_VARIABLES = [
        'site_nom' => 'Nom du site',
        'site_telephone' => 'Téléphone du site',
        'site_email' => 'Email de contact',
        'site_url' => 'Adresse du site',
    ];

    /** @return array<string, string> */
    public function availableVariables(): array
    {
        return self::RESERVATION_VARIABLES
            + ($this->key === 'quote_sent' ? self::QUOTE_VARIABLES : [])
            + self::SITE_VARIABLES;
    }

    /** Valeurs d'exemple pour l'apercu dans l'admin. @return array<string, string> */
    public static function sampleVariables(): array
    {
        return [
            'nom_client' => 'Jean Dupont', 'email_client' => 'jean.dupont@exemple.fr', 'telephone_client' => '+33 6 12 34 56 78',
            'message_client' => 'Siège enfant, s’il vous plaît.', 'numero_reservation' => '128', 'vehicule' => 'Rolls Royce Ghost',
            'date_depart' => '12/10/2026 à 09:30', 'date_retour' => '12/10/2026 à 18:00', 'lieu_depart' => 'Gare de Lyon, 75012 Paris',
            'destination' => 'Aéroport Charles de Gaulle, 95700 Roissy-en-France', 'passagers' => '3', 'montant_estime' => '1 200 €',
            'lien_admin' => url('/admin/reservations'), 'numero_devis' => 'DEV-2026-0001', 'montant_devis' => '1 440,00 € TTC',
            'date_validite' => '26/10/2026',
        ];
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function findByKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    /**
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: string, html: string, text: string}
     */
    public function render(array $variables): array
    {
        return static::renderContent($this->subject, $this->body, $variables);
    }

    /**
     * Le Markdown est converti AVANT le remplacement des variables, et les valeurs sont echappees :
     * un nom de client ne peut ni casser la mise en page ni injecter du HTML.
     *
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: string, html: string, text: string}
     */
    public static function renderContent(string $subject, string $body, array $variables): array
    {
        $html = Str::markdown($body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
        // Les liens [texte]({lien_admin}) sont encodes en %7Blien_admin%7D par le convertisseur Markdown.
        $html = preg_replace_callback('/(?:\{|%7B)([a-z_]+)(?:\}|%7D)/i', function (array $match) use ($variables) {
            return array_key_exists($match[1], $variables) ? e((string) $variables[$match[1]]) : $match[0];
        }, $html);

        $replace = fn (string $text) => preg_replace_callback('/\{([a-z_]+)\}/i', fn ($match) => array_key_exists($match[1], $variables) ? (string) $variables[$match[1]] : $match[0], $text);

        return [
            'subject' => trim(preg_replace('/\s+/', ' ', $replace($subject))),
            'html' => $html,
            'text' => $replace(preg_replace(['/^#+\s*/m', '/\*\*(.+?)\*\*/', '/\[(.+?)\]\((.+?)\)/'], ['', '$1', '$1 : $2'], $body)),
        ];
    }
}
