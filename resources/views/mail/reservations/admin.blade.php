<x-mail::message>
# Nouvelle demande de réservation

Une demande vient d'être envoyée depuis le site. Elle est **en attente** de confirmation.

<x-mail::table>
| | |
|:--|:--|
| **Référence** | #{{ $reservation->id }} |
| **Véhicule** | {{ $reservation->vehicle->name }} |
| **Départ** | {{ $reservation->start_at->format('d/m/Y à H:i') }} |
| **Retour** | {{ $reservation->end_at->format('d/m/Y à H:i') }} |
| **Durée** | {{ $reservation->days }} jour(s) |
| **Lieu** | {{ $reservation->pickup_location }} |
| **Estimation** | {{ number_format($reservation->estimated_total, 0, ',', ' ') }} € |
</x-mail::table>

## Client

<x-mail::table>
| | |
|:--|:--|
| **Nom** | {{ $reservation->customer_name }} |
| **Email** | {{ $reservation->customer_email ?: '—' }} |
| **Téléphone** | {{ $reservation->customer_phone }} |
</x-mail::table>

@if ($reservation->message)
**Message du client :**

<x-mail::panel>
{{ $reservation->message }}
</x-mail::panel>
@endif

<x-mail::button :url="$adminUrl">
Voir la réservation dans l'admin
</x-mail::button>

Répondez directement à cet email pour écrire au client.
</x-mail::message>
