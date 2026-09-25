<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Gestionnaire des vehicules en deux colonnes (reponses JSON) + formulaires classiques inchanges. */
class AdminVehicleManagerTest extends TestCase
{
    use RefreshDatabase;

    private function vehicle(array $overrides = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'name' => 'Ferrari SF90', 'category' => 'Supercar', 'fuel_type' => 'Hybride', 'transmission' => 'Auto',
            'daily_price' => 1750, 'seats' => 2, 'is_available' => true,
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Porsche 911', 'category' => 'Sportive', 'daily_price' => '900', 'seats' => '4', 'horsepower' => '450',
            'fuel_type' => 'Essence', 'transmission' => 'Auto', 'image_url' => '', 'video_url' => '', 'description' => 'Belle',
            'is_available' => '1', 'with_chauffeur' => '0', 'remove_model' => '0',
        ], $overrides);
    }

    public function test_data_lists_vehicles_with_rental_state_without_n_plus_one(): void
    {
        $this->actingAs(User::factory()->create());
        $rented = $this->vehicle();
        $this->vehicle(['name' => 'Rolls Ghost', 'category' => 'Chauffeur', 'is_available' => false]);
        Reservation::create([
            'vehicle_id' => $rented->id, 'customer_name' => 'A', 'customer_phone' => '0600000000', 'start_date' => now()->toDateString(),
            'start_at' => now('Europe/Paris')->subHour()->format('Y-m-d H:i'), 'end_at' => now('Europe/Paris')->addDay()->format('Y-m-d H:i'),
            'days' => 1, 'pickup_location' => 'Paris', 'estimated_total' => 1, 'status' => 'confirmed',
        ]);

        \DB::enableQueryLog();
        $response = $this->getJson('/admin/vehicles/data')->assertOk();
        $vehicleQueries = collect(\DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "vehicles"'))->count();

        $this->assertSame(1, $vehicleQueries, 'La liste doit etre chargee en une seule requete');
        $response->assertJsonCount(2, 'vehicles')
            ->assertJsonPath('categories', ['Chauffeur', 'Supercar'])
            ->assertJsonFragment(['name' => 'Ferrari SF90', 'reserved_now' => true, 'public_url' => route('vehicles.show', 'ferrari-sf90')])
            ->assertJsonFragment(['name' => 'Rolls Ghost', 'reserved_now' => false, 'public_url' => null]);
    }

    public function test_json_create_update_delete(): void
    {
        $this->actingAs(User::factory()->create());

        $created = $this->postJson('/admin/vehicles', $this->validPayload())->assertCreated()->json('vehicle');
        $this->assertSame('porsche-911', $created['slug']);
        $this->assertSame(4, $created['seats']);

        $this->postJson("/admin/vehicles/{$created['id']}", $this->validPayload(['_method' => 'PUT', 'daily_price' => '950', 'is_available' => '0']))
            ->assertOk()
            ->assertJsonPath('vehicle.daily_price', 950)
            ->assertJsonPath('vehicle.is_available', false);

        $this->deleteJson("/admin/vehicles/{$created['id']}")->assertOk();
        $this->assertDatabaseMissing('vehicles', ['id' => $created['id']]);
    }

    public function test_json_validation_errors_are_returned_per_field(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/admin/vehicles', $this->validPayload(['name' => '', 'seats' => '12', 'video_url' => 'pas-une-url']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'seats', 'video_url']);
    }

    public function test_photo_and_model_upload_through_the_manager(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $vehicle = $this->vehicle();

        $payload = $this->validPayload([
            '_method' => 'PUT',
            'image' => UploadedFile::fake()->image('voiture.jpg', 800, 500),
            'model' => UploadedFile::fake()->create('voiture.glb', 100),
        ]);

        $json = $this->post("/admin/vehicles/{$vehicle->id}", $payload, ['Accept' => 'application/json'])->assertOk()->json('vehicle');
        $vehicle->refresh();

        Storage::disk('public')->assertExists($vehicle->image_path);
        Storage::disk('public')->assertExists($vehicle->model_path);
        $this->assertStringEndsWith('.glb', $vehicle->model_path);
        $this->assertNotNull($json['model_url']);
    }

    public function test_classic_forms_still_redirect(): void
    {
        $this->actingAs(User::factory()->create());
        $vehicle = $this->vehicle();

        $this->post('/admin/vehicles', $this->validPayload())->assertRedirect(route('admin.vehicles.index'));
        $this->put("/admin/vehicles/{$vehicle->id}", $this->validPayload(['name' => 'Ferrari Roma']))->assertRedirect(route('admin.vehicles.index'));
        $this->assertSame('Ferrari Roma', $vehicle->fresh()->name);
        $this->delete("/admin/vehicles/{$vehicle->id}")->assertRedirect(route('admin.vehicles.index'));
    }

    public function test_manager_page_mounts_the_island_with_a_fallback_table(): void
    {
        $this->actingAs(User::factory()->create());
        $this->vehicle();

        $this->get('/admin/vehicles?vehicle=1')
            ->assertOk()
            ->assertSee('data-island="VehicleManager"', false)
            ->assertSee('Ferrari SF90');
    }
}
