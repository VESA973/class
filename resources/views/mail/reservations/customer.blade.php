<x-mail::message>
# Merci {{ $reservation->customer_name }} !

Nous avons bien reçu votre demande de réservation. Notre équipe vous recontacte rapidement pour la **confirmer**.

<x-mail::table>
| | |
|:--|:--|
| **Référence** | #{{ $reservation->id }} |
| **Véhicule** | {{ $reservation->vehicle->name }} |
| **Départ** | {{ $reservation->start_at->format('d/m/Y à H:i') }} |
| **Retour** | {{ $reservation->end_at->format('d/m/Y à H:i') }} |
| **Lieu de prise en charge** | {{ $reservation->pickup_location }} |
| **Estimation** | {{ number_format($reservation->estimated_total, 0, ',', ' ') }} € |
</x-mail::table>

Cette demande ne vaut pas confirmation : le véhicule vous est réservé une fois votre réservation confirmée par notre équipe.

Une question ? Appelez-nous au **{{ $contactPhone }}** ou répondez à cet email.

À très bientôt,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
