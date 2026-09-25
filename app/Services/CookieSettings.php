<?php

namespace App\Services;

/** Bandeau cookies : textes, couleurs, categories et scripts (Admin > Cookies). */
class CookieSettings
{
    /** Duree de validite d'un choix : 6 mois maximum (recommandation CNIL). */
    public const MAX_AGE_DAYS = 180;

    public const CATEGORIES = ['analytics', 'marketing'];

    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $categories = (array) $this->settings->get('cookies.categories', []);

        return [
            'enabled' => (bool) $this->settings->get('cookies.enabled', true),
            'version' => (int) $this->settings->get('cookies.policy_version', 1),
            'texts' => array_merge([
                'title' => 'Vos choix en matière de cookies',
                'message' => 'Nous utilisons des cookies nécessaires au fonctionnement du site. Avec votre accord, nous pouvons aussi mesurer l’audience et personnaliser nos communications. Vous pouvez changer d’avis à tout moment avec le lien « Gérer mes cookies ».',
                'accept' => 'Tout accepter',
                'reject' => 'Tout refuser',
                'customize' => 'Personnaliser',
                'save' => 'Enregistrer mes choix',
            ], array_filter((array) $this->settings->get('cookies.texts', []))),
            'colors' => array_merge([
                'background' => '#18181b',
                'text' => '#fafafa',
                'button' => '#e4e4e7',
                'button_text' => '#0a0a0a',
            ], array_filter((array) $this->settings->get('cookies.colors', []))),
            'categories' => [
                'necessary' => [
                    'label' => 'Cookies nécessaires',
                    'description' => 'Indispensables au fonctionnement du site (session, sécurité des formulaires, mémorisation de vos choix). Ils ne peuvent pas être désactivés.',
                    'scripts' => '',
                    'cookies' => '',
                ],
                'analytics' => array_merge([
                    'label' => 'Mesure d’audience',
                    'description' => 'Nous aident à comprendre la fréquentation du site pour l’améliorer (statistiques anonymisées).',
                    'scripts' => '',
                    'cookies' => '_ga, _gid, _gat',
                ], array_filter((array) ($categories['analytics'] ?? []), fn ($value) => $value !== null)),
                'marketing' => array_merge([
                    'label' => 'Marketing',
                    'description' => 'Permettent de mesurer l’efficacité de nos publicités et de vous proposer des offres adaptées.',
                    'scripts' => '',
                    'cookies' => '_fbp, _gcl_au',
                ], array_filter((array) ($categories['marketing'] ?? []), fn ($value) => $value !== null)),
            ],
        ];
    }
}
