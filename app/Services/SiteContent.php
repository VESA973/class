<?php

namespace App\Services;

/**
 * Contenus modifiables dans l'admin qui n'ont pas de table dediee :
 * atouts de la page d'accueil et types de prestation proposes a la reservation.
 */
class SiteContent
{
    public const MAX_ADVANTAGES = 12;

    public const MAX_SERVICE_TYPES = 30;

    /** Icones proposees pour les atouts : nom => libelle. */
    public const ICONS = [
        'sparkles' => 'Étoiles',
        'car-front' => 'Voiture',
        'user-round' => 'Chauffeur',
        'users' => 'Groupe',
        'truck' => 'Livraison',
        'clock' => 'Horloge',
        'calendar-check' => 'Calendrier',
        'map-pin' => 'Lieu',
        'shield-check' => 'Sécurité',
        'star' => 'Étoile',
        'headset' => 'Assistance',
        'phone' => 'Téléphone',
        'gauge' => 'Performance',
        'send' => 'Envoi',
    ];

    public const DEFAULT_SERVICE_TYPES = [
        'Location sans chauffeur',
        'Location avec chauffeur',
        'Transfert aéroport',
        'Mariage',
        'Évènement / soirée',
        'Voyage d’affaires',
    ];

    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return list<array{icon: string, title: string, text: string}> */
    public function advantages(): array
    {
        $saved = $this->settings->get('home.advantages');

        return is_array($saved) ? array_values($saved) : (array) config('home.advantages_defaults', config('home.advantages'));
    }

    /** @return list<string> */
    public function serviceTypes(): array
    {
        $saved = $this->settings->get('booking.service_types');

        return is_array($saved) ? array_values($saved) : self::DEFAULT_SERVICE_TYPES;
    }

    /** Remplace les atouts de config/home.php par ceux de l'admin (appele a chaque requete). */
    public function apply(): void
    {
        if (config('home.advantages_defaults') === null) {
            config(['home.advantages_defaults' => config('home.advantages')]);
        }

        config(['home.advantages' => $this->advantages()]);
    }
}
