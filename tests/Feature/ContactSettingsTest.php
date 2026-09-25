<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\ContactSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Coordonnees modifiables dans l'admin + bouton WhatsApp. */
class ContactSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function save(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs(User::factory()->create())->put('/admin/coordonnees', array_merge([
            'phone' => '+33 6 00 00 00 01',
            'email' => 'hello@example.com',
            'address' => '1 rue de Test, Paris',
            'whatsapp_enabled' => '1',
            'whatsapp_number' => '06 12 34 56 78',
            'whatsapp_message' => 'Bonjour !',
        ], $overrides));
    }

    public function test_guest_cannot_open_contact_settings(): void
    {
        $this->get('/admin/coordonnees')->assertRedirect(route('admin.login'));
    }

    public function test_admin_page_shows_current_values(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/coordonnees')
            ->assertOk()
            ->assertSee('+33 1 80 11 44 83')
            ->assertSee('Coordonnées & WhatsApp');
    }

    public function test_new_phone_is_used_across_the_site(): void
    {
        $this->save()->assertRedirect(route('admin.contact.edit'))->assertSessionHasNoErrors();

        $this->assertSame('+33 6 00 00 00 01', config('home.contact.phone'));
        $this->assertSame('+33600000001', config('home.contact.phone_href'));
        $this->assertSame('+33 6 00 00 00 01', config('booking.contact_phone'));

        auth()->logout();
        $this->get('/contact')->assertOk()
            ->assertSee('tel:+33600000001', false)
            ->assertSee('hello@example.com')
            ->assertDontSee('+33 1 80 11 44 83');
    }

    public function test_whatsapp_button_links_to_wa_me_with_normalized_number(): void
    {
        $this->save();
        auth()->logout();

        $this->get('/')->assertOk()
            ->assertSee('https://wa.me/33612345678?text=Bonjour%20%21', false)
            ->assertSee('Nous écrire sur WhatsApp');
    }

    public function test_vehicle_page_prefills_vehicle_name(): void
    {
        $vehicle = Vehicle::create(['name' => 'Rolls Royce Ghost', 'category' => 'Chauffeur', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'seats' => 5, 'daily_price' => 1200, 'is_available' => true]);
        $this->save();
        auth()->logout();

        $this->get(route('vehicles.show', $vehicle))->assertOk()
            ->assertSee('whatsapp-fab--raised', false)
            ->assertSee(rawurlencode('Rolls Royce Ghost'), false);
    }

    public function test_whatsapp_button_hidden_when_disabled(): void
    {
        $this->save(['whatsapp_enabled' => null]);
        auth()->logout();

        $this->get('/')->assertOk()->assertDontSee('wa.me', false);
    }

    public function test_enabling_whatsapp_requires_a_valid_number(): void
    {
        $this->save(['whatsapp_number' => ''])->assertSessionHasErrors('whatsapp_number');
        $this->save(['phone' => 'appelez-nous'])->assertSessionHasErrors('phone');
    }

    public function test_whatsapp_number_normalization(): void
    {
        $this->assertSame('33612345678', ContactSettings::normalizeWhatsapp('06 12 34 56 78'));
        $this->assertSame('33612345678', ContactSettings::normalizeWhatsapp('+33 6 12 34 56 78'));
        $this->assertSame('33612345678', ContactSettings::normalizeWhatsapp('0033 6 12 34 56 78'));
        $this->assertSame('14155552671', ContactSettings::normalizeWhatsapp('+1 (415) 555-2671'));
        $this->assertSame('+33180114483', ContactSettings::telHref('+33 1 80 11 44 83'));
        $this->assertSame('0180114483', ContactSettings::telHref('01 80 11 44 83'));
    }
}
