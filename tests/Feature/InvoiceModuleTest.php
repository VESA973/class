<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    private function quote()
    {
        return app(QuoteService::class)->create(
            ['customer_name' => 'Jean Dupont', 'customer_email' => 'jean@example.com', 'issued_at' => now(), 'valid_until' => now()->addDays(15), 'discount_type' => 'none', 'discount_value' => 0],
            [['description' => 'Location', 'quantity' => 2, 'unit_price_ht' => 100, 'vat_rate' => 0]],
        );
    }

    public function test_invoice_is_created_once_from_a_quote_with_continuous_numbering(): void
    {
        $quote = $this->quote();

        $this->post(route('admin.invoices.store', $quote))->assertRedirect();
        $this->post(route('admin.invoices.store', $quote))->assertRedirect();
        $this->post(route('admin.invoices.store', $this->quote()))->assertRedirect();

        $invoices = Invoice::orderBy('id')->get();
        $this->assertCount(2, $invoices);
        $prefix = 'F'.now(config('app.local_timezone'))->format('Ym').'-';
        $this->assertSame([$prefix.'01', $prefix.'02'], $invoices->pluck('number')->all());
        $this->assertSame('200.00', $invoices[0]->total_ttc);
        $this->assertCount(1, $invoices[0]->lines);
        $this->assertSame('accepted', $quote->fresh()->status);
    }

    public function test_pdf_send_and_partial_payments(): void
    {
        Mail::fake();
        $invoice = app(InvoiceService::class)->createFromQuote($this->quote());

        $this->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee($invoice->number);
        $this->get(route('admin.invoices.index'))->assertOk()->assertSee($invoice->number);

        $this->post(route('admin.invoices.send', $invoice))->assertSessionHasNoErrors();
        Mail::assertSent(TemplatedMail::class);
        $this->assertNotNull($invoice->fresh()->sent_at);

        // Acompte puis solde ; un trop-percu est refuse.
        $this->post(route('admin.invoices.payments.store', $invoice), ['amount' => 60, 'paid_at' => '2026-10-15', 'method' => 'virement'])->assertSessionHasNoErrors();
        $this->assertSame('Partiellement payée', $invoice->fresh()->status_label);
        $this->assertSame(140.0, $invoice->fresh()->balance);
        $this->get(route('admin.invoices.pdf', $invoice))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->post(route('admin.invoices.payments.store', $invoice), ['amount' => 150, 'paid_at' => '2026-10-20'])->assertSessionHasErrors('payment');
        $this->post(route('admin.invoices.payments.store', $invoice), ['amount' => 140, 'paid_at' => '2026-10-20'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('2026-10-20', $invoice->fresh()->paid_at->toDateString());

        $payment = $invoice->payments()->latest('id')->first();
        $this->delete(route('admin.invoices.payments.destroy', [$invoice, $payment]))->assertRedirect();
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->paid_at);
    }

    public function test_credit_note_cancels_invoice_and_quote_can_be_invoiced_again(): void
    {
        $quote = $this->quote();
        $invoice = app(InvoiceService::class)->createFromQuote($quote);

        $this->post(route('admin.invoices.credit-note', $invoice))->assertRedirect();
        $this->post(route('admin.invoices.credit-note', $invoice))->assertSessionHasErrors('invoice');

        $creditNote = Invoice::where('type', 'credit_note')->sole();
        $this->assertSame('A'.now(config('app.local_timezone'))->format('Ym').'-01', $creditNote->number);
        $this->assertSame($invoice->id, $creditNote->credited_invoice_id);
        $this->assertSame('200.00', $creditNote->total_ttc);
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->get(route('admin.invoices.pdf', $creditNote))->assertOk();

        $this->post(route('admin.invoices.store', $quote));
        $this->assertSame(2, Invoice::where('quote_id', $quote->id)->where('type', 'invoice')->count());
    }

    public function test_blank_invoice_without_quote_uses_configured_due_days(): void
    {
        app(Settings::class)->set(['invoices.due_days' => 0]);

        $this->get(route('admin.invoices.create'))->assertOk()->assertSee('Nouvelle facture');
        $this->post(route('admin.invoices.store-blank'), [
            'customer_name' => 'Marie Martin', 'issued_at' => '2000-01-01', 'valid_until' => now()->toDateString(),
            'discount_type' => 'none', 'vat_exempt' => 1,
            'lines' => [['description' => 'Transfert aéroport', 'quantity' => 1, 'unit_price_ht' => 90, 'vat_rate' => 20]],
        ])->assertRedirect();

        $invoice = Invoice::sole();
        $this->assertNull($invoice->quote_id);
        $this->assertSame('90.00', $invoice->total_ttc); // sans TVA : taux force a 0
        $this->assertSame(now(config('app.local_timezone'))->toDateString(), $invoice->issued_at->toDateString());
        $this->assertSame(0, app(InvoiceService::class)->dueDays());
    }
}
