<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Quote;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QuoteService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuoteModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Les PDF generes pendant les tests ne doivent pas atterrir dans le vrai dossier des devis.
        Storage::fake('local');
    }

    private function reservation(array $overrides = []): Reservation
    {
        $vehicle = Vehicle::create(['name' => 'Rolls Ghost', 'category' => 'Chauffeur', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'daily_price' => 1200, 'is_available' => true]);

        return Reservation::create(array_merge([
            'vehicle_id' => $vehicle->id, 'customer_name' => 'Jean Dupont', 'customer_email' => 'jean@example.com', 'customer_phone' => '0600000000',
            'start_date' => '2030-03-10', 'end_date' => '2030-03-11', 'start_at' => '2030-03-10 09:00', 'end_at' => '2030-03-12 09:00',
            'days' => 2, 'pickup_location' => 'Gare de Lyon', 'destination' => 'Orly', 'passengers' => 3, 'estimated_total' => 2400, 'status' => 'pending',
        ], $overrides));
    }

    private function lines(): array
    {
        return [
            ['description' => 'Location', 'quantity' => 2, 'unit_price_ht' => 100, 'vat_rate' => 20],
            ['description' => 'Chauffeur', 'quantity' => 1, 'unit_price_ht' => 50, 'vat_rate' => 10],
        ];
    }

    public function test_totals_with_percent_discount_and_several_vat_rates(): void
    {
        $totals = QuoteService::compute($this->lines(), 'percent', 10);

        $this->assertSame(250.0, $totals['subtotal_ht']);
        $this->assertSame(25.0, $totals['discount_ht']);
        $this->assertSame(225.0, $totals['total_ht']);
        $this->assertSame(40.5, $totals['total_vat']); // 200*0.9*20% + 50*0.9*10%
        $this->assertSame(265.5, $totals['total_ttc']);
    }

    public function test_totals_rounding_and_amount_discount_capped(): void
    {
        $totals = QuoteService::compute([['description' => 'x', 'quantity' => 3, 'unit_price_ht' => 33.33, 'vat_rate' => 20]]);
        $this->assertSame(99.99, $totals['total_ht']);
        $this->assertSame(20.0, $totals['total_vat']);
        $this->assertSame(119.99, $totals['total_ttc']);

        $capped = QuoteService::compute($this->lines(), 'amount', 999);
        $this->assertSame(250.0, $capped['discount_ht']);
        $this->assertSame(0.0, $capped['total_ttc']);
    }

    public function test_quote_is_created_from_a_request_with_sequential_numbers(): void
    {
        $this->actingAs(User::factory()->create());
        $reservation = $this->reservation();

        $this->post("/admin/reservations/{$reservation->id}/devis")->assertRedirect();
        $this->post("/admin/reservations/{$reservation->id}/devis")->assertRedirect();

        $year = now('Europe/Paris')->year;
        $this->assertSame(["DEV-{$year}-0001", "DEV-{$year}-0002"], Quote::orderBy('id')->pluck('number')->all());

        $quote = Quote::first()->load('lines');
        $this->assertSame('Jean Dupont', $quote->customer_name);
        $this->assertSame(1, $quote->lines->count());
        $this->assertSame('2.00', $quote->lines[0]->quantity);
        $this->assertSame('1000.00', $quote->lines[0]->unit_price_ht); // 1 200 TTC -> 1 000 HT
        $this->assertSame('2400.00', $quote->total_ttc);
        $this->assertStringContainsString('Rolls Ghost', $quote->lines[0]->description);
        $this->assertSame('in_progress', $reservation->fresh()->request_status);
        $this->assertDatabaseHas('reservation_events', ['reservation_id' => $reservation->id, 'type' => 'quote_created']);
    }

    public function test_update_recomputes_totals_server_side_and_validates(): void
    {
        $this->actingAs(User::factory()->create());
        $quote = app(QuoteService::class)->createFromReservation($this->reservation());

        $this->put("/admin/devis/{$quote->id}", [
            'customer_name' => 'Jean Dupont', 'issued_at' => '2030-01-01', 'valid_until' => '2030-01-15',
            'discount_type' => 'percent', 'discount_value' => 10, 'lines' => $this->lines(), 'total_ttc' => 1,
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('265.50', $quote->total_ttc);
        $this->assertSame(2, $quote->lines()->count());

        $this->put("/admin/devis/{$quote->id}", ['customer_name' => '', 'issued_at' => '2030-01-10', 'valid_until' => '2030-01-01', 'discount_type' => 'percent', 'discount_value' => 150, 'lines' => []])
            ->assertSessionHasErrors(['customer_name', 'valid_until', 'discount_value', 'lines']);
    }

    public function test_pdf_is_generated(): void
    {
        $this->actingAs(User::factory()->create());
        $quote = app(QuoteService::class)->createFromReservation($this->reservation());

        $response = $this->get("/admin/devis/{$quote->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_quote_is_sent_with_pdf_attachment_and_statuses_follow(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $reservation = $this->reservation();
        $quote = app(QuoteService::class)->createFromReservation($reservation);

        $this->post("/admin/devis/{$quote->id}/envoyer", ['to' => 'jean@example.com'])->assertSessionHas('status');

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('jean@example.com')
            && str_contains($mail->mailSubject, $quote->number)
            && $mail->files[0]['name'] === $quote->number.'.pdf'
            && is_file($mail->files[0]['path']));
        $this->assertSame('sent', $quote->fresh()->status);
        $this->assertSame('quote_sent', $reservation->fresh()->request_status);

        $this->patch("/admin/devis/{$quote->id}/statut", ['status' => 'accepted'])->assertRedirect();
        $this->assertSame('accepted', $reservation->fresh()->request_status);
        $this->assertTrue($reservation->events()->where('type', 'quote_accepted')->exists());

        // Un devis envoye ne peut pas etre supprime.
        $this->delete("/admin/devis/{$quote->id}")->assertForbidden();
    }

    public function test_send_without_email_or_with_smtp_failure_reports_an_error(): void
    {
        $this->actingAs(User::factory()->create());
        $quote = app(QuoteService::class)->createFromReservation($this->reservation(['customer_email' => null]));

        $this->post("/admin/devis/{$quote->id}/envoyer")->assertSessionHasErrors('send');
        $this->assertSame('draft', $quote->fresh()->status);

        app(Settings::class)->set(['mail.mode' => 'smtp', 'mail.smtp.host' => '127.0.0.1', 'mail.smtp.port' => 1, 'mail.smtp.encryption' => 'none']);
        app(\App\Services\MailSettings::class)->apply();
        $this->post("/admin/devis/{$quote->id}/envoyer", ['to' => 'x@example.com'])->assertSessionHasErrors('send');
        $this->assertSame('draft', $quote->fresh()->status);
        $this->assertDatabaseHas('email_logs', ['recipient' => 'x@example.com', 'status' => 'failed']);
    }

    public function test_request_status_and_history(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Admin']));
        $reservation = $this->reservation();

        $this->patch("/admin/reservations/{$reservation->id}/suivi", ['request_status' => 'archived'])->assertRedirect();
        $this->assertSame('archived', $reservation->fresh()->request_status);

        $this->get("/admin/reservations/{$reservation->id}")->assertOk()->assertSee('Suivi : Nouvelle → Archivée')->assertSee('Admin');
        $this->get('/admin/reservations?request_status=archived')->assertOk()->assertSee('Jean Dupont');
        $this->get('/admin/reservations?request_status=new')->assertOk()->assertDontSee('Jean Dupont');
    }

    public function test_new_booking_logs_history_and_auto_send_stays_disabled_by_default(): void
    {
        Mail::fake();
        $vehicle = Vehicle::create(['name' => 'Ferrari', 'category' => 'Supercar', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'daily_price' => 1500, 'is_available' => true]);
        $payload = ['vehicle_id' => $vehicle->id, 'start_at' => '2030-05-10T09:00', 'end_at' => '2030-05-10T18:00', 'pickup_location' => 'Paris',
            'customer_name' => 'Client', 'customer_email' => 'client@example.com', 'customer_phone' => '0600000000'];

        $id = $this->withoutDefer()->postJson('/api/reservations', $payload)->assertCreated()->json('reservation.id');
        $this->assertDatabaseHas('reservation_events', ['reservation_id' => $id, 'type' => 'created']);
        $this->assertSame(0, Quote::count());

        // Option activee : devis genere et envoye automatiquement.
        app(Settings::class)->set(['quotes.auto_send' => true]);
        $payload['start_at'] = '2030-06-10T09:00';
        $payload['end_at'] = '2030-06-10T18:00';
        $id = $this->withoutDefer()->postJson('/api/reservations', $payload)->assertCreated()->json('reservation.id');

        $this->assertSame('sent', Quote::where('reservation_id', $id)->value('status'));
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('client@example.com') && str_contains($mail->mailSubject, 'DEV-'));
    }

    public function test_admin_pages_and_settings(): void
    {
        $this->actingAs(User::factory()->create());
        $quote = app(QuoteService::class)->createFromReservation($this->reservation());

        foreach (['/admin/devis', "/admin/devis/{$quote->id}", '/admin/devis/reglages', '/admin', '/admin/reservations'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->put('/admin/devis/reglages', [
            'vat_rate' => '10', 'validity_days' => 30, 'line_template' => 'Transfert {vehicule}', 'conditions' => 'Acompte 30 %',
            'company' => ['name' => 'CLASS AFFAIRE SAS', 'siret' => '12345678900012'],
        ])->assertRedirect();

        $config = app(QuoteService::class)->config();
        $this->assertSame('10', $config['vat_rate']);
        $this->assertFalse($config['auto_send']);
        $this->assertSame('CLASS AFFAIRE SAS', $config['company']['name']);
    }
}
