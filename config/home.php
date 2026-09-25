<?php

/*
 * Contenu editable de la nouvelle page d'accueil (/nouvelle-accueil).
 * Les textes reprennent les informations deja presentes sur le site.
 */
return [

    'hero' => [
        'eyebrow' => 'Location premium avec ou sans chauffeur',
        'title' => 'Des voitures d’exception, réservées en quelques clics.',
        'text' => 'Une sélection de SUV, supercars et berlines avec réservation rapide, livraison sur demande et service chauffeur pour vos déplacements privés ou professionnels.',
        'stats' => [
            ['value' => '24/7', 'label' => 'Disponibilité'],
            ['value' => '30 min', 'label' => 'Réponse moyenne'],
            ['value' => 'Paris', 'label' => 'Cannes et Roissy'],
        ],
    ],

    'advantages' => [
        ['icon' => 'sparkles', 'title' => 'Une flotte d’exception', 'text' => 'SUV, supercars et berlines de prestige, sélectionnés et entretenus avec soin.'],
        ['icon' => 'user-round', 'title' => 'Avec ou sans chauffeur', 'text' => 'Conduisez vous-même ou confiez le volant à un chauffeur discret et ponctuel.'],
        ['icon' => 'truck', 'title' => 'Livraison sur demande', 'text' => 'Le véhicule vous attend à l’adresse de votre choix : domicile, hôtel, aéroport.'],
        ['icon' => 'clock', 'title' => 'Disponible 24/7', 'text' => 'Une équipe joignable à toute heure, avec une réponse en 30 minutes en moyenne.'],
        ['icon' => 'map-pin', 'title' => 'Paris, Cannes et Roissy', 'text' => 'Transferts aéroport, évènements et voyages d’affaires sur nos zones de service.'],
        ['icon' => 'shield-check', 'title' => 'Réservation sans surprise', 'text' => 'Disponibilités en temps réel, estimation immédiate et confirmation par notre équipe.'],
    ],

    'steps' => [
        ['icon' => 'calendar-days', 'title' => 'Choisissez', 'text' => 'Sélectionnez votre véhicule, vos dates et vos horaires : les disponibilités s’affichent en temps réel.'],
        ['icon' => 'send', 'title' => 'Envoyez votre demande', 'text' => 'Indiquez vos coordonnées et le lieu de prise en charge. Aucun paiement en ligne.'],
        ['icon' => 'calendar-check', 'title' => 'Profitez', 'text' => 'Notre équipe vous recontacte rapidement pour confirmer, puis le véhicule vous attend.'],
    ],

    /*
     * Avis clients : la section n'est affichee que si cette liste contient de vrais avis.
     * Exemple : ['name' => 'Prénom N.', 'context' => 'Mariage, juin 2026', 'rating' => 5, 'text' => '...'],
     */
    'testimonials' => [],

    'contact' => [
        'phone' => '+33 1 80 11 44 83',
        'phone_href' => '+33180114483',
        'email' => 'contact@classaffaire.fr',
        'address' => '174 Rue de la Belle Etoile, Roissy Charles de Gaulle',
    ],

];
