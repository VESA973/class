<?php

return [

    /*
    | Adresse qui recoit un email a chaque nouvelle reservation en ligne.
    | Vide : l'email administrateur n'est pas envoye (un avertissement est journalise).
    */
    'admin_email' => env('ADMIN_EMAIL'),

    // Email de confirmation envoye au client apres sa demande.
    'send_customer_confirmation' => (bool) env('BOOKING_CUSTOMER_EMAIL', true),

    // Coordonnees affichees dans les emails.
    'contact_phone' => env('BOOKING_CONTACT_PHONE', '+33 1 80 11 44 83'),

];
