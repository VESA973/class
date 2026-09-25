<?php

namespace App\Services;

/**
 * Coordonnees du site (Admin > Parametres > Coordonnees & WhatsApp).
 *
 * Les valeurs saisies dans l'admin remplacent celles de config/home.php au demarrage :
 * en-tete, pied de page, page Contact, emails, devis, pages legales, maintenance et SEO
 * utilisent donc automatiquement le numero a jour.
 */
class ContactSettings
{
    /** Indicatif => [libelle, code pays ISO (schema.org)]. Sert a convertir les numeros nationaux (06…, 0694…). */
    public const COUNTRIES = [
        '594' => ['Guyane française', 'GF'],
        '33' => ['France métropolitaine', 'FR'],
        '590' => ['Guadeloupe, Saint-Martin, Saint-Barthélemy', 'GP'],
        '596' => ['Martinique', 'MQ'],
        '262' => ['La Réunion, Mayotte', 'RE'],
    ];

    /** Le site est base en Guyane. */
    public const DEFAULT_COUNTRY_CODE = '594';

    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return array{phone: string, phone_href: string, email: string, address: string, country_code: string, country_iso: string, whatsapp_enabled: bool, whatsapp_number: string, whatsapp_message: string} */
    public function values(): array
    {
        // Valeurs d'origine (config/home.php), memorisees avant toute surcharge.
        if (config('home.contact_defaults') === null) {
            config(['home.contact_defaults' => (array) config('home.contact')]);
        }

        $defaults = config('home.contact_defaults');
        $countryCode = (string) $this->settings->get('contact.country_code', self::DEFAULT_COUNTRY_CODE);
        $countryCode = isset(self::COUNTRIES[$countryCode]) ? $countryCode : self::DEFAULT_COUNTRY_CODE;
        $phone = $this->settings->get('contact.phone') ?: $defaults['phone'];

        return [
            'phone' => $phone,
            'phone_href' => self::telHref($phone, $countryCode),
            'email' => $this->settings->get('contact.email') ?: $defaults['email'],
            'address' => $this->settings->get('contact.address') ?: $defaults['address'],
            'country_code' => $countryCode,
            'country_iso' => self::COUNTRIES[$countryCode][1],
            'whatsapp_enabled' => (bool) $this->settings->get('contact.whatsapp_enabled', false),
            'whatsapp_number' => (string) $this->settings->get('contact.whatsapp_number', ''),
            'whatsapp_message' => $this->settings->get('contact.whatsapp_message') ?: 'Bonjour, je souhaite des informations sur la location d’un véhicule.',
        ];
    }

    /** Applique les coordonnees a la configuration du site (appele a chaque requete). */
    public function apply(): void
    {
        $values = $this->values();

        config([
            'home.contact' => [
                'phone' => $values['phone'],
                'phone_href' => $values['phone_href'],
                'email' => $values['email'],
                'address' => $values['address'],
                'country_code' => $values['country_code'],
                'country_iso' => $values['country_iso'],
            ],
            'booking.contact_phone' => $values['phone'],
        ]);
    }

    /** Lien WhatsApp (wa.me) avec message pre-rempli, ou null si le bouton est desactive. */
    public function whatsappUrl(?string $message = null): ?string
    {
        $values = $this->values();

        if (! $values['whatsapp_enabled'] || $values['whatsapp_number'] === '') {
            return null;
        }

        return 'https://wa.me/'.$values['whatsapp_number'].'?text='.rawurlencode($message ?: $values['whatsapp_message']);
    }

    /**
     * Lien tel: au format international.
     * "05 94 12 34 56" (Guyane) -> "+594594123456" ; "+33 1 80 11 44 83" -> "+33180114483".
     */
    public static function telHref(string $phone, string $countryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        return '+'.self::toInternational($phone, $countryCode);
    }

    /**
     * Numero WhatsApp au format international sans + ni 00 (exige par wa.me).
     * Guyane : "06 94 12 34 56" -> "594694123456" ; "+33 6 12..." ou "0033 6 12..." -> "336 12...".
     */
    public static function normalizeWhatsapp(string $number, string $countryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        return trim($number) === '' ? '' : self::toInternational($number, $countryCode);
    }

    /** Chiffres au format international (sans +). Un numero national (0 + 9 chiffres) recoit l'indicatif du pays choisi. */
    private static function toInternational(string $number, string $countryCode): string
    {
        $number = trim($number);
        $digits = preg_replace('/\D/', '', $number);

        if (str_starts_with($number, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return $countryCode.substr($digits, 1);
        }

        return $digits;
    }
}
