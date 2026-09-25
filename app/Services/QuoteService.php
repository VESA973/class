<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Reservation;
use App\Models\ReservationEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Devis : creation a partir d'une demande, calcul des totaux, PDF et envoi par email.
 *
 * Envoi automatique (prepare, DESACTIVE par defaut) : handleNewReservation() est appele a chaque
 * nouvelle demande ; tant que l'option « quotes.auto_send » est desactivee dans Admin > Devis > Reglages,
 * il ne fait rien (la demande est seulement creee et l'admin notifie). Activee, elle appelle
 * generate() (devis construit a partir du tarif du vehicule et du modele par defaut) puis send().
 */
class QuoteService
{
    public const PDF_DIRECTORY = 'quotes';

    public function __construct(
        private readonly Settings $settings,
        private readonly EmailService $emails,
    ) {
    }

    /** Reglages des devis (modele par defaut, informations de l'entreprise). @return array<string, mixed> */
    public function config(): array
    {
        return [
            'vat_rate' => (string) $this->settings->get('quotes.vat_rate', '20'),
            'validity_days' => (int) $this->settings->get('quotes.validity_days', 15),
            'prices_include_vat' => (bool) $this->settings->get('quotes.prices_include_vat', true),
            'line_template' => $this->settings->get('quotes.line_template') ?: 'Location {vehicule} du {date_depart} au {date_retour}',
            'conditions' => $this->settings->get('quotes.conditions') ?: "Devis valable jusqu'à la date indiquée. La réservation est confirmée à réception du devis signé ou de votre accord écrit.",
            'auto_send' => (bool) $this->settings->get('quotes.auto_send', false),
            'company' => array_merge([
                'name' => '', 'legal_form' => '', 'siret' => '', 'vat_number' => '', 'address' => '',
                'email' => config('home.contact.email'), 'phone' => config('home.contact.phone'), 'iban' => '',
            ], (array) $this->settings->get('quotes.company', [])),
        ];
    }

    /** Brouillon pre-rempli (client, vehicule, dates, tarif) a partir d'une demande. */
    public function createFromReservation(Reservation $reservation): Quote
    {
        $reservation->loadMissing('vehicle');
        $config = $this->config();
        $variables = ReservationMailer::variables($reservation);
        $vatRate = (float) $config['vat_rate'];
        $unitPrice = (float) ($reservation->vehicle?->daily_price ?? 0);

        if ($config['prices_include_vat'] && $vatRate > 0) {
            $unitPrice = round($unitPrice / (1 + $vatRate / 100), 2);
        }

        $description = preg_replace_callback('/\{([a-z_]+)\}/', fn ($m) => $variables[$m[1]] ?? $m[0], $config['line_template']);

        $quote = $this->create([
            'reservation_id' => $reservation->id,
            'customer_name' => $reservation->customer_name,
            'customer_email' => $reservation->customer_email,
            'customer_phone' => $reservation->customer_phone,
            'subject' => 'Location '.($reservation->vehicle?->name ?? 'de véhicule'),
            'issued_at' => now('Europe/Paris')->toDateString(),
            'valid_until' => now('Europe/Paris')->addDays($config['validity_days'])->toDateString(),
            'discount_type' => 'none',
            'discount_value' => 0,
            'conditions' => $config['conditions'],
        ], [[
            'description' => $description,
            'quantity' => max(1, (int) $reservation->days),
            'unit_price_ht' => $unitPrice,
            'vat_rate' => $vatRate,
        ]]);

        if (in_array($reservation->request_status, ['new', null], true)) {
            $reservation->update(['request_status' => 'in_progress']);
        }

        ReservationEvent::record($reservation, 'quote_created', "Devis {$quote->number} créé (brouillon).", ['quote_id' => $quote->id]);

        return $quote;
    }

    /**
     * Cree un devis avec un numero unique DEV-AAAA-NNNN (verrou : pas de doublon si deux devis sont crees en meme temps).
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(array $data, array $lines): Quote
    {
        return DB::transaction(function () use ($data, $lines) {
            $year = (int) now('Europe/Paris')->format('Y');
            $sequence = (int) Quote::query()->where('year', $year)->lockForUpdate()->max('sequence') + 1;

            $quote = Quote::create($data + [
                'year' => $year,
                'sequence' => $sequence,
                'number' => sprintf('DEV-%d-%04d', $year, $sequence),
                'status' => 'draft',
            ]);

            $this->saveLines($quote, $lines);

            return $quote;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function update(Quote $quote, array $data, array $lines): Quote
    {
        DB::transaction(function () use ($quote, $data, $lines) {
            $quote->update($data);
            $quote->lines()->delete();
            $this->saveLines($quote, $lines);
        });

        if ($quote->reservation) {
            ReservationEvent::record($quote->reservation, 'quote_updated', "Devis {$quote->number} modifié.", ['quote_id' => $quote->id]);
        }

        return $quote->refresh();
    }

    /**
     * Totaux calcules en centimes (aucune erreur d'arrondi). La remise s'applique sur le HT,
     * la TVA est recalculee taux par taux sur le montant remise.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return array{lines: list<array<string, mixed>>, subtotal_ht: float, discount_ht: float, total_ht: float, total_vat: float, total_ttc: float, vat_breakdown: array<string, float>}
     */
    public static function compute(array $lines, string $discountType = 'none', float $discountValue = 0): array
    {
        $computed = [];
        $subtotal = 0;
        $htByRate = [];

        foreach (array_values($lines) as $index => $line) {
            $quantity = round((float) $line['quantity'], 2);
            $unit = (int) round((float) $line['unit_price_ht'] * 100);
            $rate = (string) (float) $line['vat_rate'];
            $total = (int) round($quantity * $unit);

            $subtotal += $total;
            $htByRate[$rate] = ($htByRate[$rate] ?? 0) + $total;
            $computed[] = [
                'position' => $index,
                'description' => trim((string) $line['description']),
                'quantity' => $quantity,
                'unit_price_ht' => (float) ($unit / 100),
                'vat_rate' => (float) $rate,
                'total_ht' => (float) ($total / 100),
            ];
        }

        $discount = match ($discountType) {
            'percent' => (int) round($subtotal * min(max($discountValue, 0), 100) / 100),
            'amount' => min((int) round($discountValue * 100), $subtotal),
            default => 0,
        };
        $ratio = $subtotal > 0 ? ($subtotal - $discount) / $subtotal : 0;

        $vat = 0;
        $breakdown = [];
        foreach ($htByRate as $rate => $ht) {
            $rateVat = (int) round($ht * $ratio * (float) $rate / 100);
            $vat += $rateVat;
            $breakdown[$rate] = (float) ($rateVat / 100);
        }

        $totalHt = $subtotal - $discount;

        return [
            'lines' => $computed,
            // (float) : 25000 / 100 donnerait l'entier 250 en PHP ; les montants restent toujours decimaux.
            'subtotal_ht' => (float) ($subtotal / 100),
            'discount_ht' => (float) ($discount / 100),
            'total_ht' => (float) ($totalHt / 100),
            'total_vat' => (float) ($vat / 100),
            'total_ttc' => (float) (($totalHt + $vat) / 100),
            'vat_breakdown' => $breakdown,
        ];
    }

    public function pdf(Quote $quote): string
    {
        $quote->loadMissing('lines', 'reservation.vehicle');
        $totals = self::compute($quote->lines->map(fn ($line) => $line->only(['description', 'quantity', 'unit_price_ht', 'vat_rate']))->all(), $quote->discount_type, (float) $quote->discount_value);

        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'totals' => $totals,
            'company' => $this->config()['company'],
        ])->setPaper('a4')->setOption([
            'isRemoteEnabled' => false,       // aucune ressource externe chargee
            'defaultFont' => 'DejaVu Sans',   // accents et symbole euro
            'isFontSubsettingEnabled' => true, // seuls les caracteres utilises sont inclus (PDF leger)
        ])->output();
    }

    /** PDF enregistre dans storage/app/private/quotes (non accessible depuis le web). */
    public function storePdf(Quote $quote): string
    {
        $path = self::PDF_DIRECTORY.'/'.$quote->number.'.pdf';
        Storage::disk('local')->put($path, $this->pdf($quote));
        $quote->update(['pdf_path' => $path]);

        return $path;
    }

    /** Envoie le devis au client (modele « Envoi de devis », PDF joint). */
    public function send(Quote $quote, ?string $to = null): void
    {
        $to = $to ?: $quote->customer_email;

        if (! $to) {
            throw new RuntimeException("Aucune adresse email pour ce client : ajoutez-la au devis avant l'envoi.");
        }

        $path = $this->storePdf($quote);
        $reservation = $quote->reservation;
        $variables = ($reservation ? ReservationMailer::variables($reservation) : ['nom_client' => $quote->customer_name]) + [
            'nom_client' => $quote->customer_name,
            'numero_devis' => $quote->number,
            'montant_devis' => Quote::money($quote->total_ttc).' TTC',
            'date_validite' => $quote->valid_until->format('d/m/Y'),
        ];

        $log = $this->emails->sendTemplate('quote_sent', $to, $variables, [
            'reservation_id' => $reservation?->id,
            'files' => [['path' => Storage::disk('local')->path($path), 'name' => $quote->number.'.pdf', 'mime' => 'application/pdf']],
        ]);

        if (! $log) {
            throw new RuntimeException('Le modèle d’email « Envoi de devis » est désactivé (Admin › Emails › Modèles).');
        }

        if ($log->status === 'failed') {
            if ($reservation) {
                ReservationEvent::record($reservation, 'quote_send_failed', "Échec de l'envoi du devis {$quote->number} à {$to}.", ['error' => $log->error]);
            }

            throw new RuntimeException("L'email n'a pas pu être envoyé : ".$log->error);
        }

        $quote->update(['status' => 'sent', 'sent_at' => now()]);

        if ($reservation) {
            $reservation->update(['request_status' => 'quote_sent']);
            ReservationEvent::record($reservation, 'quote_sent', "Devis {$quote->number} envoyé à {$to} (".Quote::money($quote->total_ttc).' TTC).', ['quote_id' => $quote->id, 'email_log_id' => $log->id]);
        }
    }

    public function markAs(Quote $quote, string $status): void
    {
        $quote->update(['status' => $status]);

        if ($quote->reservation && in_array($status, ['accepted', 'refused'], true)) {
            $quote->reservation->update(['request_status' => $status]);
            ReservationEvent::record($quote->reservation, 'quote_'.$status, "Devis {$quote->number} ".($status === 'accepted' ? 'accepté' : 'refusé').' par le client.', ['quote_id' => $quote->id]);
        }
    }

    // ------------------------------------------------------------------ Envoi automatique (desactive)

    /** Appele a chaque nouvelle demande (evenement ReservationCreated). Ne fait rien tant que l'option est desactivee. */
    public function handleNewReservation(Reservation $reservation): void
    {
        if (! $this->config()['auto_send']) {
            return;
        }

        try {
            $this->send($this->generate($reservation));
        } catch (Throwable $exception) {
            Log::error('Envoi automatique du devis impossible.', ['reservation_id' => $reservation->id, 'error' => $exception->getMessage()]);
        }
    }

    /** Devis construit automatiquement (tarif du vehicule x duree, modele par defaut). */
    public function generate(Reservation $reservation): Quote
    {
        return $this->createFromReservation($reservation);
    }

    /** @param list<array<string, mixed>> $lines */
    private function saveLines(Quote $quote, array $lines): void
    {
        $totals = self::compute($lines, $quote->discount_type ?? 'none', (float) $quote->discount_value);

        foreach ($totals['lines'] as $line) {
            $quote->lines()->create($line);
        }

        $quote->update([
            'subtotal_ht' => $totals['subtotal_ht'],
            'discount_ht' => $totals['discount_ht'],
            'total_ht' => $totals['total_ht'],
            'total_vat' => $totals['total_vat'],
            'total_ttc' => $totals['total_ttc'],
        ]);
    }
}
