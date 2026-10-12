<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Quote;
use App\Models\ReservationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Factures et avoirs : creation (depuis un devis ou libre), paiements, PDF (modele des devis) et envoi par email. */
class InvoiceService
{
    public const PDF_DIRECTORY = 'invoices';

    public function __construct(
        private readonly QuoteService $quotes,
        private readonly EmailService $emails,
        private readonly Settings $settings,
    ) {
    }

    /** Delai de paiement en jours (Admin > Devis > Reglages). */
    public function dueDays(): int
    {
        return (int) $this->settings->get('invoices.due_days', 30);
    }

    /**
     * Emet une facture (ou un avoir) : numero FAAAAMM-NN / AAAAAMM-NN sans trou ni doublon (verrou), totaux recalcules.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(array $data, array $lines): Invoice
    {
        return DB::transaction(function () use ($data, $lines) {
            $now = now(config('app.local_timezone'));
            $type = $data['type'] ?? 'invoice';
            $prefix = ($type === 'credit_note' ? 'A' : 'F').$now->format('Ym').'-';
            $sequence = (int) Invoice::query()->where('number', 'like', $prefix.'%')->lockForUpdate()->max('sequence') + 1;
            $data['vat_exempt'] = (bool) ($data['vat_exempt'] ?? false);

            if ($data['vat_exempt']) {
                $lines = array_map(fn (array $line) => ['vat_rate' => 0] + array_diff_key($line, ['vat_rate' => true]), $lines);
            }

            $totals = QuoteService::compute($lines, $data['discount_type'] ?? 'none', (float) ($data['discount_value'] ?? 0));

            $invoice = Invoice::create(array_intersect_key($totals, array_flip(['subtotal_ht', 'discount_ht', 'total_ht', 'total_vat', 'total_ttc'])) + $data + [
                'type' => $type,
                'year' => (int) $now->format('Y'),
                'sequence' => $sequence,
                'number' => sprintf('%s%02d', $prefix, $sequence),
                'status' => $type === 'credit_note' ? 'credit' : 'issued',
                'issued_at' => $now->toDateString(),
                'due_at' => $now->copy()->addDays($this->dueDays())->toDateString(),
            ]);

            foreach ($totals['lines'] as $line) {
                $invoice->lines()->create($line);
            }

            return $invoice;
        });
    }

    /** Facture le devis (une seule facture active par devis) : lignes, client et remise copies. */
    public function createFromQuote(Quote $quote): Invoice
    {
        $quote->loadMissing('lines');

        if ($quote->lines->isEmpty()) {
            throw new RuntimeException('Le devis n’a aucune ligne à facturer.');
        }

        if ($existing = Invoice::where('quote_id', $quote->id)->where('type', 'invoice')->where('status', '!=', 'cancelled')->first()) {
            return $existing;
        }

        $invoice = $this->create($quote->only([
            'reservation_id', 'customer_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'subject',
            'discount_type', 'discount_value', 'vat_exempt',
        ]) + ['quote_id' => $quote->id], $this->copyLines($quote));

        if ($quote->status !== 'accepted') {
            $quote->update(['status' => 'accepted']);
        }

        $this->record($invoice, 'invoice_created', "Facture {$invoice->number} émise (".Quote::money($invoice->total_ttc).').');

        return $invoice;
    }

    /** Avoir total : annule la facture (seule facon legale de corriger une facture emise). */
    public function creditNote(Invoice $invoice): Invoice
    {
        if ($invoice->is_credit_note || $invoice->status === 'cancelled') {
            throw new RuntimeException('Cette facture a déjà été annulée par un avoir.');
        }

        $creditNote = DB::transaction(function () use ($invoice) {
            $creditNote = $this->create($invoice->only([
                'quote_id', 'reservation_id', 'customer_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address',
                'subject', 'discount_type', 'discount_value', 'vat_exempt',
            ]) + ['type' => 'credit_note', 'credited_invoice_id' => $invoice->id], $this->copyLines($invoice));

            $invoice->update(['status' => 'cancelled']);

            return $creditNote;
        });

        $this->record($invoice, 'invoice_credited', "Facture {$invoice->number} annulée par l’avoir {$creditNote->number}.");

        return $creditNote;
    }

    /** Enregistre un reglement (acompte, partiel ou solde) ; la facture passe « Payée » une fois soldee. */
    public function addPayment(Invoice $invoice, float $amount, string $paidAt, ?string $method = null, ?string $note = null): InvoicePayment
    {
        if ($invoice->type !== 'invoice' || $invoice->status === 'cancelled') {
            throw new RuntimeException('Les paiements ne s’enregistrent que sur une facture active.');
        }

        if ($amount <= 0 || round($amount, 2) > $invoice->balance) {
            throw new RuntimeException('Montant invalide : au plus '.Quote::money($invoice->balance).' restant à régler.');
        }

        $payment = $invoice->payments()->create(['amount' => round($amount, 2), 'paid_at' => $paidAt, 'method' => $method, 'note' => $note]);
        $this->syncStatus($invoice);
        $this->record($invoice, 'invoice_payment', 'Paiement de '.Quote::money($payment->amount)." reçu sur la facture {$invoice->number}.");

        return $payment;
    }

    public function deletePayment(Invoice $invoice, InvoicePayment $payment): void
    {
        $payment->delete();
        $this->syncStatus($invoice);
    }

    public function pdf(Invoice $invoice): string
    {
        return $this->quotes->pdf($invoice);
    }

    /** Envoie la facture ou l'avoir au client (modele « Envoi de facture », PDF joint). */
    public function send(Invoice $invoice, ?string $to = null): void
    {
        $to = $to ?: $invoice->customer_email;

        if (! $to) {
            throw new RuntimeException('Aucune adresse email pour ce client.');
        }

        $path = self::PDF_DIRECTORY.'/'.$invoice->number.'.pdf';
        Storage::disk('local')->put($path, $this->pdf($invoice));
        $invoice->update(['pdf_path' => $path]);

        $log = $this->emails->sendTemplate('invoice_sent', $to, [
            'nom_client' => $invoice->customer_name,
            'type_document' => mb_strtolower($invoice->document_label),
            'numero_facture' => $invoice->number,
            'montant_facture' => Quote::money($invoice->total_ttc).($invoice->vat_exempt ? '' : ' TTC'),
            'reste_a_payer' => $invoice->is_credit_note ? 'aucun' : Quote::money($invoice->balance),
            'date_echeance' => $invoice->is_credit_note ? 'sans objet' : $invoice->due_at->format('d/m/Y'),
        ], [
            'reservation_id' => $invoice->reservation_id,
            'files' => [['path' => Storage::disk('local')->path($path), 'name' => $invoice->number.'.pdf', 'mime' => 'application/pdf']],
        ]);

        if (! $log) {
            throw new RuntimeException('Le modèle d’email « Envoi de facture » est désactivé (Admin › Emails › Modèles).');
        }

        if ($log->status === 'failed') {
            throw new RuntimeException("L'email n'a pas pu être envoyé : ".$log->error);
        }

        $invoice->update(['sent_at' => now()]);
        $this->record($invoice, 'invoice_sent', "{$invoice->document_label} {$invoice->number} envoyé(e) à {$to}.");
    }

    private function syncStatus(Invoice $invoice): void
    {
        $invoice->load('payments');
        $paid = $invoice->balance <= 0;
        $invoice->update(['status' => $paid ? 'paid' : 'issued', 'paid_at' => $paid ? $invoice->payments->max('paid_at') : null]);
    }

    /** @return list<array<string, mixed>> */
    private function copyLines(Quote|Invoice $document): array
    {
        return $document->lines()->get()->map(fn ($line) => $line->only(['description', 'quantity', 'unit_price_ht', 'vat_rate']))->all();
    }

    private function record(Invoice $invoice, string $type, string $description): void
    {
        if ($invoice->reservation) {
            ReservationEvent::record($invoice->reservation, $type, $description, ['invoice_id' => $invoice->id]);
        }
    }
}
