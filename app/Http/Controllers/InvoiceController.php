<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Quote;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/** Admin > Demandes & Devis > Factures. Pas de modification ni de suppression : une facture emise se corrige par un avoir. */
class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function index(Request $request): View
    {
        $list = Invoice::query()
            ->with('quote:id,number', 'payments')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('number', 'like', '%'.$request->input('q').'%')
                ->orWhere('customer_name', 'like', '%'.$request->input('q').'%')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $list,
            'unpaidTotal' => Invoice::where('status', 'issued')->sum('total_ttc')
                - InvoicePayment::whereHas('invoice', fn ($query) => $query->where('status', 'issued'))->sum('amount'),
        ]);
    }

    public function store(Quote $quote): RedirectResponse
    {
        try {
            $invoice = $this->invoices->createFromQuote($quote);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['invoice' => $exception->getMessage()]);
        }

        return redirect()->route('admin.invoices.show', $invoice)->with('status', $invoice->wasRecentlyCreated
            ? "Facture {$invoice->number} émise à partir du devis {$quote->number}."
            : "Ce devis est déjà facturé ({$invoice->number}).");
    }

    public function show(Invoice $invoice): View
    {
        return view('admin.invoices.show', ['invoice' => $invoice->load('lines', 'quote', 'reservation', 'customer', 'payments', 'creditNote', 'creditedInvoice')]);
    }

    public function pdf(Invoice $invoice): Response
    {
        return response($this->invoices->pdf($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->number.'.pdf"',
        ]);
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate(['to' => ['nullable', 'email', 'max:255']], ['to.email' => 'Adresse email invalide.']);

        try {
            $this->invoices->send($invoice, $data['to'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['send' => $exception->getMessage()]);
        }

        return back()->with('status', "Facture {$invoice->number} envoyée à ".($data['to'] ?? $invoice->customer_email).'.');
    }

    public function creditNote(Invoice $invoice): RedirectResponse
    {
        try {
            $creditNote = $this->invoices->creditNote($invoice);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['invoice' => $exception->getMessage()]);
        }

        return redirect()->route('admin.invoices.show', $creditNote)->with('status', "Avoir {$creditNote->number} émis : la facture {$invoice->number} est annulée.");
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:10000000'],
            'paid_at' => ['required', 'date'],
            'method' => ['nullable', Rule::in(array_keys(InvoicePayment::METHODS))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->invoices->addPayment($invoice, (float) $data['amount'], $data['paid_at'], $data['method'] ?? null, $data['note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Paiement enregistré.');
    }

    public function destroyPayment(Invoice $invoice, InvoicePayment $payment): RedirectResponse
    {
        $this->invoices->deletePayment($invoice, $payment);

        return back()->with('status', 'Paiement supprimé.');
    }
}
