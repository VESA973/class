<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Parametres : cache, mode maintenance, favicon. */
class ParametersTest extends TestCase
{
    use RefreshDatabase;

    private function enableMaintenance(array $overrides = []): void
    {
        $this->actingAs(User::factory()->create())
            ->put('/admin/parametres/maintenance', array_merge([
                'enabled' => '1', 'title' => 'On revient vite', 'message' => 'Travaux en cours', 'return_at' => '', 'allowed_ips' => '',
            ], $overrides))
            ->assertRedirect(route('admin.parameters.edit'));

        auth()->logout();
    }

    public function test_settings_are_cached_after_first_read(): void
    {
        app(Settings::class)->set(['exemple' => 'valeur']);
        app()->forgetInstance(Settings::class);

        DB::enableQueryLog();
        $this->assertSame('valeur', app(Settings::class)->get('exemple'));
        app()->forgetInstance(Settings::class);
        $this->assertSame('valeur', app(Settings::class)->get('exemple'));

        $settingsQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], '"settings"'))->count();
        $this->assertLessThanOrEqual(1, $settingsQueries);
    }

    public function test_visitors_get_503_with_retry_after_during_maintenance(): void
    {
        $this->enableMaintenance();

        $this->get('/')->assertStatus(503)->assertHeader('Retry-After', '3600')->assertSee('On revient vite')->assertSee('Travaux en cours');
        $this->get('/vehicules')->assertStatus(503);
        $this->getJson('/api/vehicles/available?start_at=2030-01-10T10:00&end_at=2030-01-11T10:00')->assertStatus(503);
    }

    public function test_retry_after_follows_the_planned_return_date(): void
    {
        $this->enableMaintenance(['return_at' => now('Europe/Paris')->addHours(2)->format('Y-m-d\TH:i')]);

        $retryAfter = (int) $this->get('/')->assertStatus(503)->headers->get('Retry-After');
        $this->assertEqualsWithDelta(7200, $retryAfter, 120);
    }

    public function test_back_office_admins_and_allowed_ips_still_see_the_site(): void
    {
        $this->enableMaintenance(['allowed_ips' => "203.0.113.10\n198.51.100.0/24"]);

        $this->get('/admin/login')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->get('/')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.42'])->get('/')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])->get('/')->assertStatus(503);

        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Mode maintenance activé');
        $this->get('/admin')->assertOk()->assertSee('Mode maintenance activé');
    }

    public function test_invalid_ip_is_rejected_and_maintenance_can_be_disabled(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->put('/admin/parametres/maintenance', ['enabled' => '1', 'allowed_ips' => 'pas-une-ip'])
            ->assertSessionHasErrors('allowed_ips');

        $this->enableMaintenance();
        $this->actingAs($admin)->put('/admin/parametres/maintenance', ['enabled' => '0'])->assertRedirect();
        auth()->logout();
        $this->get('/')->assertOk();
    }

    public function test_maintenance_preview_is_available_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/parametres/maintenance/apercu')->assertOk()->assertSee('Site en maintenance');
    }

    public function test_favicon_upload_generates_every_size_and_head_tags(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post('/admin/parametres/favicon', ['favicon' => UploadedFile::fake()->image('logo.png', 512, 512)])
            ->assertRedirect(route('admin.parameters.edit'))
            ->assertSessionHasNoErrors();

        foreach (['favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png', 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'favicon.ico'] as $file) {
            Storage::disk('public')->assertExists("favicon/{$file}");
        }

        [$width] = getimagesizefromstring(Storage::disk('public')->get('favicon/apple-touch-icon.png'));
        $this->assertSame(180, $width);
        $ico = unpack('vreserved/vtype/vcount', Storage::disk('public')->get('favicon/favicon.ico'));
        $this->assertSame(['reserved' => 0, 'type' => 1, 'count' => 3], $ico);

        $this->get('/')->assertSee('favicon/apple-touch-icon.png', false)->assertSee('site.webmanifest', false);
        $this->getJson('/site.webmanifest')->assertOk()->assertJsonCount(2, 'icons')->assertJsonPath('icons.1.sizes', '512x512');

        $this->delete('/admin/parametres/favicon')->assertRedirect();
        Storage::disk('public')->assertMissing('favicon/favicon.ico');
        $this->get('/')->assertDontSee('favicon/apple-touch-icon.png', false);
    }

    public function test_non_square_png_is_centered_and_too_small_png_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post('/admin/parametres/favicon', ['favicon' => UploadedFile::fake()->image('petit.png', 100, 100)])
            ->assertSessionHasErrors('favicon');

        $this->post('/admin/parametres/favicon', ['favicon' => UploadedFile::fake()->image('large.png', 800, 400)])
            ->assertSessionHasNoErrors();
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get('favicon/android-chrome-512x512.png'));
        $this->assertSame([512, 512], [$width, $height]);
    }

    public function test_unsafe_or_unsupported_svg_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $svg = UploadedFile::fake()->createWithContent('icone.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="10" height="10"/></svg>');

        $this->post('/admin/parametres/favicon', ['favicon' => $svg])->assertSessionHasErrors('favicon');
        Storage::disk('public')->assertMissing('favicon/icon.svg');
    }
}
