<?php

namespace Tests\Feature;

use App\Jobs\SendLoggedEmail;
use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\MailSettings;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailModuleTest extends TestCase
{
    use RefreshDatabase;

    private function book(): \Illuminate\Testing\TestResponse
    {
        $vehicle = Vehicle::create(['name' => 'Rolls Ghost', 'category' => 'Chauffeur', 'fuel_type' => 'Essence', 'transmission' => 'Auto', 'daily_price' => 1200, 'is_available' => true]);

        return $this->withoutDefer()->postJson('/api/reservations', [
            'vehicle_id' => $vehicle->id, 'start_at' => '2030-03-10T09:00', 'end_at' => '2030-03-10T18:00',
            'pickup_location' => 'Gare de Lyon', 'customer_name' => 'Jean <b>Dupont</b>', 'customer_email' => 'jean@example.com',
            'customer_phone' => '+33 6 12 34 56 78',
        ]);
    }

    public function test_new_reservation_sends_admin_and_customer_emails_from_templates(): void
    {
        Mail::fake();
        app(Settings::class)->set(['mail.admin_email' => 'admin@example.com']);

        $id = $this->book()->assertCreated()->json('reservation.id');

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('admin@example.com')
            && str_contains($mail->mailSubject, "Nouvelle demande n°{$id}")
            && $mail->hasReplyTo('jean@example.com')
            && str_contains($mail->bodyHtml, route('admin.reservations.show', $id)));
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('jean@example.com')
            && str_contains($mail->bodyHtml, 'Jean &lt;b&gt;Dupont&lt;/b&gt;'));

        $this->assertSame(2, EmailLog::where('status', 'sent')->where('reservation_id', $id)->count());
    }

    public function test_disabled_template_is_not_sent(): void
    {
        Mail::fake();
        EmailTemplate::findByKey('reservation_received_customer')->update(['is_active' => false]);

        $this->book()->assertCreated();

        Mail::assertNotSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('jean@example.com'));
    }

    public function test_smtp_failure_is_logged_and_does_not_block_the_booking(): void
    {
        app(Settings::class)->set([
            'mail.mode' => 'smtp', 'mail.smtp.host' => '127.0.0.1', 'mail.smtp.port' => 1, 'mail.smtp.encryption' => 'none',
            'mail.admin_email' => 'admin@example.com',
        ]);
        app(MailSettings::class)->apply();

        $this->book()->assertCreated();

        $log = EmailLog::where('recipient', 'admin@example.com')->firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertNotEmpty($log->error);
    }

    public function test_smtp_settings_are_saved_encrypted_and_applied(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put('/admin/emails', [
            'mode' => 'smtp', 'host' => 'ssl0.ovh.net', 'port' => 465, 'encryption' => 'ssl', 'username' => 'resa@exemple.fr',
            'password' => 'MotDePasseSecret', 'from_address' => 'resa@exemple.fr', 'from_name' => 'CLASS AFFAIRE', 'admin_email' => 'admin@exemple.fr',
        ])->assertRedirect(route('admin.emails.settings'));

        $stored = app(Settings::class)->get('mail.smtp.password');
        $this->assertNotSame('MotDePasseSecret', $stored);
        $this->assertSame('MotDePasseSecret', Crypt::decryptString($stored));
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('resa@exemple.fr', config('mail.from.address'));

        // Mot de passe laisse vide : l'ancien est conserve.
        $this->put('/admin/emails', ['mode' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls', 'from_address' => 'resa@exemple.fr']);
        $this->assertSame('MotDePasseSecret', Crypt::decryptString(app(Settings::class)->get('mail.smtp.password')));
        $this->assertTrue(config('mail.mailers.smtp.require_tls'));

        $this->get('/admin/emails')->assertOk()->assertDontSee('MotDePasseSecret');
    }

    public function test_smtp_mode_requires_a_host(): void
    {
        $this->actingAs(User::factory()->create());
        $this->put('/admin/emails', ['mode' => 'smtp', 'encryption' => 'tls', 'from_address' => 'a@b.fr'])->assertSessionHasErrors(['host', 'port']);
    }

    public function test_test_email_is_sent_and_logged(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());

        $this->post('/admin/emails/test', ['to' => 'moi@example.com'])->assertSessionHas('status');

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('moi@example.com'));
        $this->assertDatabaseHas('email_logs', ['recipient' => 'moi@example.com', 'template_key' => 'test', 'status' => 'sent']);
    }

    public function test_queue_option_dispatches_a_job(): void
    {
        Queue::fake();
        app(Settings::class)->set(['mail.use_queue' => true, 'mail.admin_email' => 'admin@example.com']);

        $this->book()->assertCreated();

        Queue::assertPushed(SendLoggedEmail::class, 2);
        $this->assertSame(2, EmailLog::where('status', 'queued')->count());
    }

    public function test_template_admin_pages_update_and_preview(): void
    {
        $this->actingAs(User::factory()->create());
        $template = EmailTemplate::findByKey('reservation_admin_notification');

        foreach (['/admin/emails', '/admin/emails/modeles', "/admin/emails/modeles/{$template->id}", '/admin/emails/historique'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->put("/admin/emails/modeles/{$template->id}", ['subject' => 'Nouvelle demande {vehicule}', 'body' => "# Bonjour\n\nClient : {nom_client}", 'is_active' => '1'])
            ->assertRedirect();
        $this->assertSame('Nouvelle demande {vehicule}', $template->fresh()->subject);

        $this->postJson("/admin/emails/modeles/{$template->id}/apercu", ['subject' => 'Objet {vehicule}', 'body' => '[Ouvrir]({lien_admin})'])
            ->assertOk()
            ->assertJsonPath('subject', 'Objet Rolls Royce Ghost')
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'href="'.url('/admin/reservations').'"'))->etc());

        $this->put("/admin/emails/modeles/{$template->id}", ['subject' => '', 'body' => ''])->assertSessionHasErrors(['subject', 'body']);
    }
}
