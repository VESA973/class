<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CustomerDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Base clients : creation depuis le site sans blocage, doublons, fusion, lien avec les devis. */
class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->vehicle = Vehicle::create(['name' => 'Range Rover', 'category' => 'SUV', 'fuel_type' => 'Diesel', 'transmission' => 'Auto', 'seats' => 5, 'daily_price' => 500, 'is_available' => true]);
    }

    private function book(array $overrides = [], string $day = '2030-03-10'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('api.reservations.store'), array_merge([
            'vehicle_id' => $this->vehicle->id, 'start_at' => $day.'T09:00', 'end_at' => $day.'T18:00',
            'pickup_location' => 'Cayenne', 'destination' => 'Kourou', 'passengers' => 2, 'prestation_type' => 'Mariage',
            'customer_first_name' => 'Marie', 'customer_last_name' => 'Joseph', 'customer_company' => '',
            'customer_email' => 'Marie.Joseph@Example.com', 'customer_phone' => '06 94 12 34 56',
        ], $overrides));
    }

    public function test_booking_creates_a_customer_record(): void
    {
        $this->book()->assertCreated();

        $customer = Customer::sole();
        $this->assertSame('Marie', $customer->first_name);
        $this->assertSame('Joseph', $customer->last_name);
        $this->assertSame('marie.joseph@example.com', $customer->email);
        $this->assertSame('06 94 12 34 56', $customer->phone_mobile);
        $this->assertSame('594694123456', $customer->phone_mobile_key);
        $this->assertSame('site', $customer->source);
        $this->assertSame($customer->id, Reservation::sole()->customer_id);
        $this->assertSame('Marie Joseph', Reservation::sole()->customer_name);
    }

    public function test_returning_customer_is_never_blocked_and_not_duplicated(): void
    {
        $this->book()->assertCreated();
        // Meme email (autre casse), autre fixe, sur d'autres dates : accepte et rattache a la meme fiche.
        $this->book(['customer_email' => 'marie.joseph@example.com', 'customer_phone' => '05 94 30 20 10'], '2030-04-10')->assertCreated();

        $this->assertSame(1, Customer::count());
        $customer = Customer::sole();
        $this->assertSame(2, $customer->reservations()->count());
        $this->assertSame('05 94 30 20 10', $customer->phone_landline); // nouveau numero conserve
    }

    public function test_same_phone_with_other_email_is_accepted_and_flagged_as_duplicate(): void
    {
        $this->book()->assertCreated();
        $this->book(['customer_email' => 'autre@example.com', 'customer_phone' => '+594 694 12 34 56'], '2030-04-10')->assertCreated();

        $this->assertSame(2, Customer::count());
        $groups = app(CustomerDirectory::class)->duplicateGroups();
        $this->assertCount(1, $groups);
        $this->assertContains('même téléphone', $groups[0]['reasons']);
        $this->assertContains('même nom', $groups[0]['reasons']);

        $this->actingAs(User::factory()->create())->get('/admin/clients/doublons')->assertOk()->assertSee('Groupe 1')->assertSee('même téléphone');
        $this->get('/admin/clients')->assertOk()->assertSee('Vérifier les doublons');
    }

    public function test_old_single_name_field_still_works(): void
    {
        $this->book(['customer_first_name' => null, 'customer_last_name' => null, 'customer_name' => 'Jean-Paul Martin'])->assertCreated();

        $customer = Customer::sole();
        $this->assertSame('Jean-Paul', $customer->first_name);
        $this->assertSame('Martin', $customer->last_name);
    }

    public function test_company_booking_creates_a_professional_customer(): void
    {
        $this->book(['customer_company' => 'Guyane Events SARL'])->assertCreated();

        $customer = Customer::sole();
        $this->assertSame('professionnel', $customer->type);
        $this->assertSame('Guyane Events SARL (Marie Joseph)', $customer->display_name);
    }

    public function test_merge_moves_requests_and_quotes_and_fills_blanks(): void
    {
        $this->book()->assertCreated();
        $this->book(['customer_email' => 'autre@example.com'], '2030-04-10')->assertCreated();
        [$primary, $duplicate] = Customer::orderBy('id')->get()->all();
        $duplicate->update(['city' => 'Kourou', 'notes' => 'Client fidèle']);

        $this->actingAs(User::factory()->create());
        $quoteId = Quote::create(['customer_id' => $duplicate->id, 'number' => 'DEV-2030-0001', 'year' => 2030, 'sequence' => 1, 'status' => 'draft', 'customer_name' => 'x', 'issued_at' => '2030-01-01', 'valid_until' => '2030-01-15', 'discount_type' => 'none', 'discount_value' => 0])->id;

        $this->post('/admin/clients/fusion', ['primary' => $primary->id, 'merge' => [$duplicate->id]])->assertRedirect(route('admin.customers.duplicates'));

        $primary->refresh();
        $this->assertSame('Kourou', $primary->city);
        $this->assertStringContainsString('Client fidèle', $primary->notes);
        $this->assertSame(2, $primary->reservations()->count());
        $this->assertSame($primary->id, Quote::find($quoteId)->customer_id);
        $this->assertSoftDeleted('customers', ['id' => $duplicate->id, 'merged_into_id' => $primary->id]);
        $this->assertSame([], app(CustomerDirectory::class)->duplicateGroups());

        $this->post('/admin/clients/fusion', ['primary' => $primary->id, 'merge' => []])->assertSessionHasErrors('merge');
    }

    public function test_admin_creates_edits_and_searches_customers_with_non_blocking_duplicate_warning(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/clients/nouveau')->assertOk()->assertSee('Créer le client');

        $this->post('/admin/clients', [
            'type' => 'professionnel', 'company_name' => 'Amazonie Voyages', 'first_name' => 'Luc', 'last_name' => 'Horth',
            'email' => 'luc@amazonie.gf', 'phone_mobile' => '0694000001', 'phone_landline' => '0594000001', 'city' => 'Cayenne',
            'license_number' => '12AB34567', 'license_issued_at' => '2010-05-01', 'birth_date' => '1980-02-03',
        ])->assertRedirect()->assertSessionMissing('duplicate_warning');
        $customer = Customer::sole();

        // Doublon : enregistre quand meme, avec avertissement.
        $this->post('/admin/clients', ['type' => 'particulier', 'last_name' => 'Autre', 'phone_mobile' => '+594 694 00 00 01'])
            ->assertRedirect()->assertSessionHas('duplicate_warning');
        $this->assertSame(2, Customer::count());

        $this->post('/admin/clients', ['type' => 'professionnel', 'last_name' => 'Sans societe'])->assertSessionHasErrors('company_name');
        $this->post('/admin/clients', ['type' => 'particulier'])->assertSessionHasErrors('last_name');

        $this->get(route('admin.customers.edit', $customer))->assertOk()->assertSee('Amazonie Voyages')->assertSee('12AB34567');
        $this->put(route('admin.customers.update', $customer), ['type' => 'professionnel', 'company_name' => 'Amazonie Voyages', 'last_name' => 'Horth', 'city' => 'Kourou'])->assertRedirect();
        $this->assertSame('Kourou', $customer->fresh()->city);

        $this->get('/admin/clients?q=amazonie')->assertSee('Amazonie Voyages');
        $this->get('/admin/clients?q=luc horth')->assertSee('Amazonie Voyages');
        $this->getJson('/admin/clients/recherche?q=0694000001')->assertJsonPath('customers.0.id', $customer->id);
        $this->getJson('/admin/clients/recherche?q=luc@')->assertJsonPath('customers.0.name', 'Amazonie Voyages');

        $this->delete(route('admin.customers.destroy', $customer))->assertRedirect();
        $this->assertSoftDeleted($customer);
    }

    public function test_quotes_use_the_customer_base(): void
    {
        $this->actingAs(User::factory()->create());
        $this->book()->assertCreated();
        $customer = Customer::sole();
        $customer->update(['address' => '12 rue Lalouette', 'postal_code' => '97300', 'city' => 'Cayenne']);

        // Devis depuis la demande : rattache a la fiche, adresse reprise.
        $this->post('/admin/reservations/'.Reservation::sole()->id.'/devis')->assertRedirect();
        $quote = Quote::sole();
        $this->assertSame($customer->id, $quote->customer_id);
        $this->assertSame('12 rue Lalouette, 97300 Cayenne', $quote->customer_address);

        // Devis libre pour un client de la base.
        $this->get('/admin/devis/nouveau?client='.$customer->id)->assertOk()->assertSee('marie.joseph@example.com')->assertSee('Fiche client');

        // Devis libre pour un nouveau client : fiche creee (source « devis »).
        $this->post('/admin/devis', [
            'customer_name' => 'Paul Durand', 'customer_email' => 'paul@example.com', 'customer_phone' => '0694998877', 'save_customer' => '1',
            'issued_at' => '2030-01-01', 'valid_until' => '2030-01-15', 'discount_type' => 'none',
            'lines' => [['description' => 'Transfert', 'quantity' => 1, 'unit_price_ht' => 90, 'vat_rate' => 0]],
        ])->assertRedirect();
        $paul = Customer::where('email', 'paul@example.com')->sole();
        $this->assertSame(['Paul', 'Durand', 'devis'], [$paul->first_name, $paul->last_name, $paul->source]);
        $this->assertSame($paul->id, Quote::latest('id')->first()->customer_id);
    }

    public function test_sync_command_creates_customers_for_existing_requests(): void
    {
        Reservation::create([
            'vehicle_id' => $this->vehicle->id, 'customer_name' => 'Ancien Client', 'customer_email' => 'ancien@example.com', 'customer_phone' => '0694111111',
            'start_date' => '2030-01-10', 'end_date' => '2030-01-10', 'days' => 1, 'pickup_location' => 'Cayenne', 'estimated_total' => 500, 'status' => 'pending',
        ]);

        $this->artisan('customers:sync')->assertSuccessful();
        $this->artisan('customers:sync')->assertSuccessful(); // relancable sans doublon

        $customer = Customer::sole();
        $this->assertSame(['Ancien', 'Client', 'import'], [$customer->first_name, $customer->last_name, $customer->source]);
        $this->assertSame($customer->id, Reservation::sole()->customer_id);
    }
}
