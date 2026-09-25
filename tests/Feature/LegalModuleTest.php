<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_pages_are_published_with_placeholders_and_footer_links(): void
    {
        $this->get('/mentions-legales')->assertOk()
            ->assertSee('Mentions légales')
            ->assertSee('<mark class="legal-todo">[À compléter : Raison sociale]</mark>', false)
            ->assertSee('Dernière mise à jour');

        $this->get('/')->assertOk()
            ->assertSee('href="/politique-de-confidentialite"', false)
            ->assertSee('href="/conditions-generales-de-location"', false);

        $this->get('/sitemap.xml')->assertSee(url('/cgu'));
    }

    public function test_company_information_fills_legal_pages_and_quotes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->put('/admin/pages-legales/informations', [
            'raison_sociale' => 'CLASS <AFFAIRE> SAS', 'siret' => '12345678900012', 'hebergeur_nom' => 'OVH SAS',
            'mediateur_site' => 'pas-une-url',
        ])->assertSessionHasErrors('mediateur_site');

        $this->put('/admin/pages-legales/informations', ['raison_sociale' => 'CLASS <AFFAIRE> SAS', 'siret' => '12345678900012', 'hebergeur_nom' => 'OVH SAS'])
            ->assertSessionHasNoErrors();

        $this->assertSame('CLASS <AFFAIRE> SAS', app(Settings::class)->get('quotes.company')['name']);
        $this->get('/mentions-legales')->assertSee('CLASS &lt;AFFAIRE&gt; SAS', false)->assertSee('12345678900012')->assertSee('OVH SAS');
    }

    public function test_edit_sanitizes_html_and_keeps_versions(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Admin']));
        $page = LegalPage::where('key', 'terms')->first();

        $this->put("/admin/pages-legales/{$page->id}", [
            'title' => 'CGU',
            'content' => '<h2>Objet</h2><p onclick="alert(1)">Texte <a href="javascript:alert(1)">lien</a> <a href="https://exemple.fr">ok</a></p><script>alert(2)</script><img src=x onerror=alert(3)>',
            'is_published' => '1', 'note' => 'Première relecture',
        ])->assertRedirect();

        $page->refresh();
        $this->assertStringNotContainsString('script', $page->content);
        $this->assertStringNotContainsString('onclick', $page->content);
        $this->assertStringNotContainsString('javascript:', $page->content);
        $this->assertStringNotContainsString('<img', $page->content);
        $this->assertStringContainsString('href="https://exemple.fr"', $page->content);
        $this->assertSame(2, $page->versions()->count());

        // Aucune modification : pas de nouvelle version.
        $this->put("/admin/pages-legales/{$page->id}", ['title' => 'CGU', 'content' => $page->content, 'is_published' => '1']);
        $this->assertSame(2, $page->versions()->count());

        $first = \App\Models\LegalPageVersion::where("legal_page_id", $page->id)->orderBy("id")->first();
        $this->post("/admin/pages-legales/{$page->id}/versions/{$first->id}/restaurer")->assertRedirect();
        $this->assertStringContainsString('conditions générales d’utilisation', $page->fresh()->content);
        $this->assertSame(3, $page->versions()->count());
        $this->get("/admin/pages-legales/{$page->id}")->assertOk()->assertSee('Première relecture')->assertSee('Admin');
    }

    public function test_unpublished_page_is_hidden_everywhere(): void
    {
        $this->actingAs(User::factory()->create());
        $page = LegalPage::where('key', 'terms')->first();
        $this->put("/admin/pages-legales/{$page->id}", ['title' => $page->title, 'content' => $page->content]);
        auth()->logout();

        $this->get('/cgu')->assertNotFound();
        $this->get('/')->assertDontSee('href="/cgu"', false);
        $this->get('/sitemap.xml')->assertDontSee(url('/cgu').'<', false);
    }

    public function test_slug_change_redirects_and_reserved_slugs_are_refused(): void
    {
        $this->actingAs(User::factory()->create());
        $page = LegalPage::where('key', 'legal_notice')->first();

        $this->put("/admin/seo/pages-legales/{$page->id}", ['slug' => 'vehicules'])->assertSessionHasErrors('slug');
        $this->put("/admin/seo/pages-legales/{$page->id}", ['slug' => 'informations-legales'])->assertSessionHasNoErrors();

        $this->get('/mentions-legales')->assertStatus(301)->assertRedirect(url('/informations-legales'));
        $this->get('/informations-legales')->assertOk();
    }

    public function test_booking_form_links_the_privacy_policy_and_admin_pages_respond(): void
    {
        $this->get('/reserver')->assertOk()->assertSee('politique-de-confidentialite');

        $this->actingAs(User::factory()->create());
        $page = LegalPage::first();
        foreach (['/admin/pages-legales', '/admin/pages-legales/informations', "/admin/pages-legales/{$page->id}", "/admin/seo/pages-legales/{$page->id}", '/admin/seo'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
