<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Reservation;
use App\Models\ReservationEvent;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/** Admin > Demandes & Devis > Devis. */
class QuoteController extends Controller
{
    public function __construct(private readonly QuoteService $quotes)
    {
    }

    public function index(Request $request): View
    {
        $list = Quote::query()
            ->with('reservation:id,vehicle_id', 'reservation.vehicle:id,name')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('number', 'like', '%'.$request->input('q').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('q').'%')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.quotes.index', [
            'quotes' => $list,
            'pendingTotal' => Quote::where('status', 'sent')->sum('total_ttc'),
        ]);
    }

    public function store(Reservation $reservation): RedirectResponse
    {
        $quote = $this->quotes->createFromReservation($reservation);

        return redirect()->route('admin.quotes.edit', $quote)->with('status', "Devis {$quote->number} créé à partir de la demande n°{$reservation->id}. Vérifiez puis enregistrez.");
    }

    public function edit(Quote $quote): View
    {
        $quote->load('lines', 'reservation.vehicle');

        return view('admin.quotes.edit', [
            'quote' => $quote,
            'vatRates' => Quote::VAT_RATES,
            'events' => $quote->reservation?->events()->with('user:id,name')->limit(15)->get() ?? collect(),
            'company' => $this->quotes->config()['company'],
        ]);
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'subject' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:issued_at'],
            'discount_type' => ['required', Rule::in(['none', 'percent', 'amount'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:10000000', Rule::when($request->input('discount_type') === 'percent', ['max:100'])],
            'conditions' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'lines.*.unit_price_ht' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'lines.*.vat_rate' => ['required', Rule::in(array_keys(Quote::VAT_RATES))],
        ], [
            'lines.required' => 'Ajoutez au moins une ligne.',
            'lines.*.description.required' => 'Chaque ligne doit avoir une désignation.',
            'lines.*.quantity.gt' => 'La quantité doit être supérieure à 0.',
            'valid_until.after_or_equal' => 'La date de validité doit suivre la date du devis.',
            'discount_value.max' => 'Remise trop élevée (100 % maximum).',
        ]);

        $lines = array_values($data['lines']);
        unset($data['lines']);
        $data['discount_value'] = (float) ($data['discount_value'] ?? 0);

        $this->quotes->update($quote, $data, $lines);

        return redirect()->route('admin.quotes.edit', $quote)->with('status', "Devis {$quote->number} enregistré.");
    }

    public function pdf(Quote $quote): Response
    {
        return response($this->quotes->pdf($quote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quote->number.'.pdf"',
        ]);
    }

    public function send(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate(['to' => ['nullable', 'email', 'max:255']], ['to.email' => 'Adresse email invalide.']);

        try {
            $this->quotes->send($quote, $data['to'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['send' => $exception->getMessage()]);
        }

        return back()->with('status', "Devis {$quote->number} envoyé à ".($data['to'] ?? $quote->customer_email).'.');
    }

    public function status(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['accepted', 'refused', 'sent'])]]);
        $this->quotes->markAs($quote, $data['status']);

        return back()->with('status', "Devis {$quote->number} : ".Quote::STATUS_LABELS[$data['status']].'.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        abort_unless($quote->status === 'draft', 403, 'Seuls les brouillons peuvent être supprimés.');

        if ($quote->pdf_path) {
            Storage::disk('local')->delete($quote->pdf_path);
        }

        if ($quote->reservation) {
            ReservationEvent::record($quote->reservation, 'quote_deleted', "Brouillon de devis {$quote->number} supprimé.");
        }

        $reservation = $quote->reservation;
        $quote->delete();

        return $reservation
            ? redirect()->route('admin.reservations.show', $reservation)->with('status', 'Brouillon supprimé.')
            : redirect()->route('admin.quotes.index')->with('status', 'Brouillon supprimé.');
    }
}
