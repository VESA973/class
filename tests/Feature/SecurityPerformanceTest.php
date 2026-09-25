<?php

namespace Tests\Feature;

use App\Models\Prestation;
use App\Models\Quote;
use App\Models\Reservation;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_route_requires_authentication(): void
    {
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'admin') || in_array($route->getName(), ['admin.login', 'admin.login.store', 'admin.logout'], true)) {
                continue;
            }

            $url = '/'.preg_replace('/\{[^}]+\}/', '999', $uri);

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $response = $this->call($method, $url);
                $this->assertContains($response->getStatusCode(), [302, 401], "{$method} {$url} accessible sans connexion ({$response->getStatusCode()})");
                if ($response->getStatusCode() === 302) {
                    $this->assertSame(route('admin.login'), $response->headers->get('Location'));
                }
                $checked++;
            }
        }

        $this->assertGreaterThan(60, $checked);
    }

    public function test_security_headers_are_sent(): void
    {
        foreach (['/', '/admin/login'] as $url) {
            $this->get($url)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }

    public function test_public_forms_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/reservations', []);
        }
        $this->post('/reservations', [])->assertStatus(429);
    }

    /** Le nombre de requetes SQL ne doit pas augmenter avec le nombre d'elements affiches (pas de N+1). */
    public function test_lists_do_not_have_n_plus_one_queries(): void
    {
        $this->actingAs(User::factory()->create());
        $pages = ['/', '/vehicules', '/prestations', '/admin', '/admin/reservations', '/admin/devis', '/admin/vehicles/data', '/admin/emails/historique'];

        $this->seedItems(2);
        $this->countQueries($pages); // prechauffage : les caches (reglages, pied de page...) se remplissent
        $few = $this->countQueries($pages);
        $this->seedItems(10);
        $many = $this->countQueries($pages);

        foreach ($pages as $page) {
            $this->assertSame($few[$page], $many[$page], "{$page} : {$few[$page]} requêtes avec 2 éléments, {$many[$page]} avec 12");
        }
    }

    public function test_uploaded_photos_are_resized_with_a_webp_version(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post('/admin/vehicles', [
            'name' => 'Porsche 911', 'category' => 'Sportive', 'daily_price' => 900, 'fuel_type' => 'Essence', 'transmission' => 'Auto',
            'is_available' => '1', 'image' => UploadedFile::fake()->image('photo.jpg', 3000, 2000),
        ])->assertRedirect();

        $vehicle = Vehicle::firstOrFail();
        [$width, $height] = getimagesize(Storage::disk('public')->path($vehicle->image_path));
        $this->assertSame([1600, 1067], [$width, $height]);
        Storage::disk('public')->assertExists(preg_replace('/\.jpg$/', '.webp', $vehicle->image_path));

        $this->get('/vehicules')->assertSee('type="image/webp"', false);
    }

    public function test_cached_site_settings_are_refreshed_after_an_update(): void
    {
        $this->assertNull(SiteSetting::current()->hero_image_path);
        SiteSetting::current()->update(['hero_image_path' => 'site/nouvelle.jpg']);
        $this->assertSame('site/nouvelle.jpg', SiteSetting::current()->hero_image_path);
    }

    private function seedItems(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $vehicle = Vehicle::create(['name' => 'Véhicule '.uniqid(), 'category' => 'SUV', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'daily_price' => 500, 'is_available' => true]);
            Prestation::create(['name' => 'Prestation '.uniqid(), 'sort_order' => $i, 'is_active' => true]);
            $reservation = Reservation::create([
                'vehicle_id' => $vehicle->id, 'customer_name' => 'Client', 'customer_phone' => '0600000000', 'start_date' => '2030-01-10',
                'start_at' => '2030-01-10 09:00', 'end_at' => '2030-01-10 18:00', 'days' => 1, 'pickup_location' => 'Paris', 'estimated_total' => 500, 'status' => 'pending',
            ]);
            Quote::create(['reservation_id' => $reservation->id, 'number' => 'DEV-2030-'.uniqid(), 'year' => 2030, 'sequence' => random_int(1, 999999), 'customer_name' => 'Client', 'issued_at' => '2030-01-01', 'valid_until' => '2030-01-15']);
            \App\Models\EmailLog::create(['recipient' => 'a@b.fr', 'subject' => 'x', 'status' => 'sent', 'reservation_id' => $reservation->id]);
        }
    }

    /** @return array<string, int> */
    private function countQueries(array $pages): array
    {
        $counts = [];
        foreach ($pages as $page) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get($page)->assertOk();
            $counts[$page] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }

        return $counts;
    }
}
