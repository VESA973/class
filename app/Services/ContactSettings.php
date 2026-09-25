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
    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return array{phone: string, phone_href: string, email: string, address: string, whatsapp_enabled: bool, whatsapp_number: string, whatsapp_message: string} */
    public function values(): array
    {
        // Valeurs d'origine (config/home.php), memorisees avant toute surcharge.
        if (config('home.contact_defaults') === null) {
            config(['home.contact_defaults' => (array) config('home.contact')]);
        }

        $defaults = config('home.contact_defaults');
        $phone = $this->settings->get('contact.phone') ?: $defaults['phone'];

        return [
            'phone' => $phone,
            'phone_href' => self::telHref($phone),
            'email' => $this->settings->get('contact.email') ?: $defaults['email'],
            'address' => $this->settings->get('contact.address') ?: $defaults['address'],
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

    /** "+33 1 80 11 44 83" -> "+33180114483" (lien tel:). */
    public static function telHref(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', $phone);

        return str_starts_with($digits, '+') ? '+'.str_replace('+', '', $digits) : str_replace('+', '', $digits);
    }

    /**
     * Numero WhatsApp au format international sans + ni 00 (exige par wa.me).
     * "06 12 34 56 78" (France) -> "33612345678" ; "+33 6..." ou "0033 6..." -> "336...".
     */
    public static function normalizeWhatsapp(string $number): string
    {
        $number = trim($number);
        $digits = preg_replace('/\D/', '', $number);

        if (str_starts_with($number, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        // Numero francais a 10 chiffres (06, 07...) : indicatif +33.
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '33'.substr($digits, 1);
        }

        return $digits;
    }
}
