<?php

namespace Tests\Feature;

use App\Models\Prestation;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filet de securite de la refonte du back-office : toutes les pages et routes
 * existantes doivent continuer a repondre a l'identique.
 */
class ExistingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vehicle = Vehicle::create([
            'name' => 'Lamborghini Urus', 'category' => 'SUV', 'horsepower' => 641, 'fuel_type' => 'Essence',
            'transmission' => 'Auto', 'seats' => 5, 'daily_price' => 1000, 'is_available' => true,
        ]);
        Prestation::create(['name' => 'Mariages', 'sort_order' => 1, 'is_active' => true]);
    }

    public function test_public_pages_respond(): void
    {
        foreach (['/', '/vehicules', '/vehicules/lamborghini-urus', '/prestations', '/contact', '/reserver'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/vehicules/inconnu')->assertNotFound();
        $this->get('/nouvelle-accueil')->assertRedirect('/');
    }

    public function test_public_api_responds(): void
    {
        $this->getJson("/api/vehicles/{$this->vehicle->id}/booked-periods")->assertOk()->assertJsonPath('periods', []);
        $this->getJson('/api/vehicles/available?start_at=2030-01-10T10:00&end_at=2030-01-11T10:00')
            ->assertOk()
            ->assertJsonPath('vehicles.0.id', $this->vehicle->id);
    }

    public function test_online_booking_and_conflict(): void
    {
        $payload = [
            'vehicle_id' => $this->vehicle->id, 'start_at' => '2030-01-10T10:00', 'end_at' => '2030-01-11T10:00',
            'pickup_location' => 'Paris', 'customer_name' => 'Jean Test', 'customer_email' => 'jean@example.com',
            'customer_phone' => '+33 6 12 34 56 78',
        ];

        $this->postJson('/api/reservations', $payload)->assertCreated();
        $this->postJson('/api/reservations', $payload)->assertStatus(409)->assertJsonStructure(['message', 'conflicts', 'suggestions']);
    }

    public function test_guest_is_redirected_from_every_admin_page(): void
    {
        foreach ($this->adminPages() as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    public function test_admin_pages_respond_when_logged_in(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ($this->adminPages() as $url) {
            $response = $this->get($url);
            $this->assertContains($response->getStatusCode(), [200, 302], "$url a repondu {$response->getStatusCode()}");
        }

        $this->getJson('/admin/planning/events?start=2030-01-01T00:00&end=2030-02-01T00:00')->assertOk();
    }

    public function test_admin_can_update_reservation_status(): void
    {
        $this->actingAs(User::factory()->create());
        $reservation = Reservation::create([
            'vehicle_id' => $this->vehicle->id, 'customer_name' => 'Jean', 'customer_phone' => '0600000000',
            'start_date' => '2030-01-10', 'end_date' => '2030-01-10', 'days' => 1, 'pickup_location' => 'Paris',
            'estimated_total' => 1000, 'status' => 'pending',
        ]);

        $this->patch("/admin/reservations/{$reservation->id}", ['status' => 'confirmed'])->assertRedirect();
        $this->assertSame('confirmed', $reservation->fresh()->status);
        $this->patchJson("/admin/planning/reservations/{$reservation->id}/status", ['status' => 'cancelled'])->assertOk();
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_dashboard_shows_counters_and_recent_requests(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Admin']));
        Reservation::create([
            'vehicle_id' => $this->vehicle->id, 'customer_name' => 'Client Tableau', 'customer_phone' => '0600000000',
            'start_date' => '2030-01-10', 'end_date' => '2030-01-10', 'days' => 1, 'pickup_location' => 'Paris',
            'estimated_total' => 1000, 'status' => 'pending',
        ]);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Bonjour Admin')
            ->assertSee('Demandes en attente')
            ->assertSee('Client Tableau')
            ->assertSee('En attente');
    }

    /** @return list<string> */
    private function adminPages(): array
    {
        $vehicle = $this->vehicle->id;
        $prestation = Prestation::first()->id;

        return [
            '/admin', '/admin/vehicles', '/admin/vehicles/create', "/admin/vehicles/{$vehicle}/edit",
            '/admin/prestations', '/admin/prestations/create', "/admin/prestations/{$prestation}/edit",
            '/admin/reservations', '/admin/planning', '/admin/settings', '/admin/hero',
            '/admin/users', '/admin/users/create',
        ];
    }
}
