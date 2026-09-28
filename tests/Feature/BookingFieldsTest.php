<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Type de prestation, destination, passagers ; atouts de l'accueil ; adresse masquee. */
class BookingFieldsTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vehicle = Vehicle::create(['name' => 'Porsche Panamera', 'category' => 'Berline', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'seats' => 4, 'daily_price' => 800, 'is_available' => true]);
    }

    private function book(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('api.reservations.store'), array_merge([
            'vehicle_id' => $this->vehicle->id, 'start_at' => '2030-03-10T09:00', 'end_at' => '2030-03-11T09:00',
            'pickup_location' => 'Aéroport Cayenne-Félix Éboué, 97351 Matoury', 'destination' => 'Kourou, 97310', 'passengers' => 3,
            'prestation_type' => 'Transfert aéroport',
            'customer_name' => 'Marie Test', 'customer_email' => 'marie@example.com', 'customer_phone' => '0694123456',
        ], $overrides));
    }

    public function test_booking_saves_prestation_destination_and_passengers(): void
    {
        $this->book()->assertCreated()
            ->assertJsonPath('reservation.prestation_type', 'Transfert aéroport')
            ->assertJsonPath('reservation.passengers', 3);

        $reservation = Reservation::first();
        $this->assertSame('Transfert aéroport', $reservation->prestation_type);
        $this->assertSame('Kourou, 97310', $reservation->destination);
        $this->assertSame(3, $reservation->passengers);

        $this->actingAs(User::factory()->create())->get(route('admin.reservations.show', $reservation))
            ->assertOk()->assertSee('Transfert aéroport');
    }

    public function test_prestation_type_is_required_and_must_be_in_the_list(): void
    {
        $this->book(['prestation_type' => null])->assertStatus(422)->assertJsonValidationErrors('prestation_type');
        $this->book(['prestation_type' => 'Inconnu'])->assertStatus(422)->assertJsonValidationErrors('prestation_type');
    }

    public function test_destination_is_required(): void
    {
        $this->book(['destination' => ''])->assertStatus(422)->assertJsonValidationErrors(['destination' => 'Indiquez la destination.']);
    }

    public function test_prestation_type_is_optional_when_the_list_is_empty(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/types-de-prestation', ['types' => ['']])->assertSessionHasNoErrors();
        auth()->logout();

        $this->book(['prestation_type' => null])->assertCreated();
    }

    public function test_passengers_cannot_exceed_vehicle_seats(): void
    {
        $this->book(['passengers' => 5])->assertStatus(422)->assertJsonValidationErrors('passengers');
    }

    public function test_admin_manages_service_types(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/types-de-prestation')->assertOk()->assertSee('Transfert aéroport');

        $this->put('/admin/types-de-prestation', ['types' => ['Baptême', ' ', 'baptême', 'Transfert hôtel']])->assertRedirect();
        $this->assertSame(['Baptême', 'Transfert hôtel'], app(SiteContent::class)->serviceTypes());

        auth()->logout();
        $props = json_decode(html_entity_decode($this->propsOf($this->get('/reserver')->assertOk()->getContent())), true);
        $this->assertSame(['Baptême', 'Transfert hôtel'], $props['serviceTypes']);
        $this->get('/')->assertOk()->assertSee('Transfert hôtel');
        $this->book(['prestation_type' => 'Transfert hôtel'])->assertCreated();
    }

    public function test_booking_page_is_prefilled_from_home_search(): void
    {
        $response = $this->get('/reserver?prestation=Mariage&pickup=Cayenne&destination=Kourou&passengers=2&start=2030-03-10T09:00&end=2030-03-11T09:00');
        $props = json_decode(html_entity_decode($this->propsOf($response->getContent())), true);

        $this->assertSame('Mariage', $props['initialPrestation']);
        $this->assertSame('Kourou', $props['initialDestination']);
        $this->assertSame(2, $props['initialPassengers']);
        $this->assertSame('973', $props['addressTerritory']);

        // Valeurs invalides ignorees sans erreur.
        $props = json_decode(html_entity_decode($this->propsOf($this->get('/reserver?prestation=Hack&passengers=40')->getContent())), true);
        $this->assertNull($props['initialPrestation']);
        $this->assertNull($props['initialPassengers']);
    }

    public function test_admin_edits_home_advantages(): void
    {
        $this->get('/')->assertSee('Une flotte d’exception');

        $this->actingAs(User::factory()->create())->get('/admin/accueil/atouts')->assertOk()
            ->assertSee('Une flotte d’exception')->assertSee('data-repeater-template', false);
        $this->get('/admin/coordonnees')->assertOk()->assertSee('Afficher l’adresse sur le site');

        $this->put('/admin/accueil/atouts', ['advantages' => [
            ['icon' => 'map-pin', 'title' => 'Toute la Guyane', 'text' => 'Cayenne, Kourou, Saint-Laurent.'],
        ]])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/')->assertSee('Toute la Guyane')->assertDontSee('Une flotte d’exception');

        $this->actingAs(User::factory()->create())->put('/admin/accueil/atouts', ['advantages' => [['icon' => 'bad', 'title' => '', 'text' => '']]])
            ->assertSessionHasErrors(['advantages.0.icon', 'advantages.0.title']);

        $this->delete('/admin/accueil/atouts')->assertRedirect();
        auth()->logout();
        $this->get('/')->assertSee('Une flotte d’exception');
    }

    public function test_address_is_hidden_on_the_site_by_default(): void
    {
        $address = config('home.contact.address');

        $this->get('/contact')->assertOk()->assertDontSee($address);
        $this->get('/')->assertDontSee($address);

        $this->actingAs(User::factory()->create())->put('/admin/coordonnees', [
            'phone' => '0594123456', 'email' => 'a@example.com', 'address' => '1 rue de Cayenne', 'country_code' => '594', 'show_address' => '1',
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/contact')->assertSee('1 rue de Cayenne');
    }

    private function propsOf(string $html): string
    {
        preg_match('/data-island="BookingForm" data-props="([^"]+)"/', $html, $match);

        return $match[1] ?? '{}';
    }

    public function test_vehicle_without_price_shows_no_price(): void
    {
        $this->vehicle->update(['daily_price' => null]);

        $this->get(route('vehicles.show', $this->vehicle))->assertOk()->assertDontSee('/ jour')->assertDontSee('"offers"', false);
        $this->get(route('vehicles.page'))->assertOk()->assertDontSee('À partir de');

        $this->book()->assertCreated()->assertJsonPath('reservation.estimated_total', 0);

        $this->actingAs(User::factory()->create())->get(route('admin.reservations.show', Reservation::first()))->assertSee('Sur devis');

        // Admin : le prix peut etre vide
        $this->put(route('admin.vehicles.update', $this->vehicle), [
            'name' => 'Porsche Panamera', 'category' => 'Berline', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'seats' => 4, 'daily_price' => '', 'is_available' => '1',
        ], ['Accept' => 'application/json'])->assertSuccessful();
        $this->assertNull($this->vehicle->fresh()->daily_price);
    }
}
