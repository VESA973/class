<?php

namespace Tests\Feature;

use App\Models\Redirect;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoModuleTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->vehicle = Vehicle::create(['name' => 'Rolls Royce Ghost', 'category' => 'Chauffeur', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'seats' => 5, 'daily_price' => 1200, 'is_available' => true]);
    }

    public function test_pages_have_default_tags_and_structured_data(): void
    {
        $this->get('/vehicules')->assertOk()
            ->assertSee('<title>Nos véhicules - CLASS’AFFAIRE</title>', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('<link rel="canonical" href="'.url('/vehicules').'">', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('"@type":"AutoRental"', false);

        $this->get('/vehicules/rolls-royce-ghost')->assertOk()
            ->assertSee('<title>Rolls Royce Ghost - CLASS’AFFAIRE</title>', false)
            ->assertSee('"@type":"Car"', false)
            ->assertSee('"vehicleSeatingCapacity":5', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_custom_page_tags_and_noindex(): void
    {
        $this->actingAs(User::factory()->create());
        $this->put('/admin/seo/pages/contact', ['title' => 'Contactez-nous 24/7', 'description' => 'Une question ? Appelez-nous.', 'noindex' => '1'])->assertRedirect();
        auth()->logout();

        $this->get('/contact')->assertSee('<title>Contactez-nous 24/7</title>', false)
            ->assertSee('content="Une question ? Appelez-nous."', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee(url('/contact').'</loc>', false)->assertSee(url('/vehicules/rolls-royce-ghost'));
    }

    public function test_vehicle_slug_change_creates_a_301_redirect(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put("/admin/seo/vehicules/{$this->vehicle->id}", ['slug' => 'rolls-ghost', 'title' => 'Location Rolls Ghost'])->assertRedirect();
        $this->put("/admin/seo/vehicules/{$this->vehicle->id}", ['slug' => 'rolls-royce-ghost-chauffeur', 'title' => 'Location Rolls Ghost'])->assertRedirect();

        $this->get('/vehicules/rolls-royce-ghost?utm=1')->assertRedirect(url('/vehicules/rolls-royce-ghost-chauffeur').'?utm=1')->assertStatus(301);
        $this->get('/vehicules/rolls-ghost')->assertRedirect(url('/vehicules/rolls-royce-ghost-chauffeur'));
        $this->get('/vehicules/rolls-royce-ghost-chauffeur')->assertOk()->assertSee('<title>Location Rolls Ghost</title>', false);
        $this->assertSame(1, Redirect::where('from_path', '/vehicules/rolls-royce-ghost')->value('hits'));

        $this->put("/admin/seo/vehicules/{$this->vehicle->id}", ['slug' => 'Pas Valide!'])->assertSessionHasErrors('slug');
    }

    public function test_manual_redirects_with_guards(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/admin/seo/redirections', ['from_path' => '/Ancienne-Page/', 'to_url' => '/vehicules', 'status_code' => 302])->assertSessionHasNoErrors();
        $this->get('/ancienne-page')->assertStatus(302)->assertRedirect(url('/vehicules'));

        $this->post('/admin/seo/redirections', ['from_path' => '/vehicules', 'to_url' => '/ancienne-page', 'status_code' => 301])->assertSessionHasErrors('to_url');
        $this->post('/admin/seo/redirections', ['from_path' => '/admin/devis', 'to_url' => '/', 'status_code' => 301])->assertSessionHasErrors('from_path');

        $redirect = Redirect::first();
        $this->delete("/admin/seo/redirections/{$redirect->id}")->assertRedirect();
        $this->get('/ancienne-page')->assertNotFound();
    }

    public function test_robots_txt_and_settings(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));

        $this->actingAs(User::factory()->create());
        $this->put('/admin/seo/reglages', [
            'site_name' => 'CLASS AFFAIRE', 'robots_txt' => "User-agent: *\nDisallow: /reserver", 'default_og_image' => UploadedFile::fake()->image('og.jpg', 1200, 630),
            'business' => ['type' => 'TaxiService', 'name' => 'Nom </script><script>alert(1)</script>', 'email' => 'contact@example.com'],
        ])->assertRedirect();

        $this->get('/robots.txt')->assertSee('Disallow: /reserver');
        $this->get('/')->assertSee('"@type":"TaxiService"', false)->assertDontSee('</script><script>alert(1)', false)->assertSee('/storage/seo/', false);
    }

    public function test_og_image_upload_and_admin_pages(): void
    {
        $this->actingAs(User::factory()->create());
        $this->put("/admin/seo/vehicules/{$this->vehicle->id}", ['slug' => 'rolls-royce-ghost', 'og_image' => UploadedFile::fake()->image('partage.jpg', 1200, 630)])->assertRedirect();
        $this->get('/vehicules/rolls-royce-ghost')->assertSee('/storage/seo/', false);

        foreach (['/admin/seo', '/admin/seo/reglages', '/admin/seo/redirections', '/admin/seo/pages/home', "/admin/seo/vehicules/{$this->vehicle->id}"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/seo/pages/inconnue')->assertNotFound();
    }
}
