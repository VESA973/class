<?php

namespace Tests\Feature;

use App\Models\CookieConsent;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class CookieModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_is_rendered_and_scripts_stay_inert_until_consent(): void
    {
        app(Settings::class)->set(['cookies.categories' => ['analytics' => ['scripts' => '<script src="https://www.googletagmanager.com/gtag/js?id=G-TEST"></script></template><script>alert(1)</script>']]]);

        $html = $this->get('/')->assertOk()
            ->assertSee('data-cc-banner', false)
            ->assertSee('Tout refuser')
            ->assertSee('Tout accepter')
            ->assertSee('data-cookie-manage', false)
            ->getContent();

        // Le script est uniquement dans un <template> (inerte) et ne peut pas en sortir.
        $this->assertStringContainsString('<template data-cc-scripts="analytics"><script src="https://www.googletagmanager.com/gtag/js?id=G-TEST"></script><\/template>', $html);
        $this->assertSame(1, substr_count($html, 'googletagmanager'));
    }

    public function test_consent_is_recorded_without_personal_data(): void
    {
        $id = (string) Str::uuid();
        $this->postJson('/cookies/consentement', ['consent_id' => $id, 'action' => 'custom', 'analytics' => true, 'marketing' => false])->assertCreated();
        $this->postJson('/cookies/consentement', ['consent_id' => 'pas-un-uuid', 'action' => 'hack', 'analytics' => 'x', 'marketing' => false])->assertStatus(422);

        $consent = CookieConsent::sole();
        $this->assertSame([$id, 1, 'custom', true, false], [$consent->consent_id, $consent->policy_version, $consent->action, $consent->analytics, $consent->marketing]);
        $this->assertSame(['id', 'consent_id', 'policy_version', 'action', 'analytics', 'marketing', 'created_at'], array_keys($consent->getAttributes()));
    }

    public function test_admin_settings_renew_and_registry(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put('/admin/cookies', [
            'enabled' => '1',
            'texts' => ['title' => 'Cookies', 'message' => 'Votre choix', 'accept' => 'Accepter', 'reject' => 'Refuser', 'customize' => 'Choisir', 'save' => 'Valider'],
            'colors' => ['background' => '#111111', 'text' => '#ffffff', 'button' => '#eeeeee', 'button_text' => '#000000'],
            'categories' => ['analytics' => ['label' => 'Statistiques', 'description' => 'Audience', 'scripts' => '', 'cookies' => '_ga']],
        ])->assertRedirect();

        $this->post('/admin/cookies/redemander')->assertRedirect();
        $this->get('/')->assertSee('data-version="2"', false)->assertSee('Statistiques')->assertSee('--cc-bg: #111111', false);

        CookieConsent::create(['consent_id' => (string) Str::uuid(), 'policy_version' => 2, 'action' => 'reject_all']);
        $this->get('/admin/cookies/registre')->assertOk()->assertSee('Tout refusé')->assertSee('100 %');
        $this->get('/admin/cookies')->assertOk();

        $this->put('/admin/cookies', ['texts' => ['title' => 'x', 'message' => 'x', 'accept' => 'a', 'reject' => 'r', 'customize' => 'c', 'save' => 's'], 'colors' => ['background' => 'rouge']])
            ->assertSessionHasErrors('colors.background');
    }

    public function test_banner_can_be_disabled_and_old_proofs_are_pruned(): void
    {
        app(Settings::class)->set(['cookies.enabled' => false]);
        $this->get('/')->assertDontSee('data-cc-banner', false);

        CookieConsent::forceCreate(['consent_id' => (string) Str::uuid(), 'policy_version' => 1, 'action' => 'accept_all', 'created_at' => now()->subMonths(14)]);
        CookieConsent::create(['consent_id' => (string) Str::uuid(), 'policy_version' => 1, 'action' => 'accept_all']);
        Artisan::call('model:prune', ['--model' => [CookieConsent::class]]);
        $this->assertSame(1, CookieConsent::count());
    }

    public function test_contact_map_is_loaded_only_on_demand(): void
    {
        $html = $this->get('/contact')->assertOk()->assertSee('Afficher la carte')->getContent();
        $this->assertMatchesRegularExpression('/<template data-map-template>\s*<iframe/', $html);
    }
}
