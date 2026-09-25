<?php

namespace App\Services;

use App\Jobs\SendLoggedEmail;
use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Point d'entree unique pour envoyer un email du site :
 * rendu du modele, envoi (immediat ou en file d'attente) et trace dans l'historique.
 * Un echec d'envoi est journalise mais ne leve jamais d'exception vers l'appelant.
 */
class EmailService
{
    public function __construct(private readonly MailSettings $mailSettings)
    {
    }

    /**
     * @param  array<string, scalar|null>  $variables
     * @param  array{reply_to?: string|null, reply_to_name?: string|null, reservation_id?: int|null, files?: array<int, array{path: string, name: string, mime?: string}>}  $options
     */
    public function sendTemplate(string $key, string $to, array $variables, array $options = []): ?EmailLog
    {
        $template = EmailTemplate::findByKey($key);

        if (! $template || ! $template->is_active) {
            Log::info("Email « {$key} » non envoyé : modèle absent ou désactivé.", ['to' => $to]);

            return null;
        }

        $rendered = $template->render($variables + $this->siteVariables());

        return $this->send($key, $to, new TemplatedMail(
            $rendered['subject'],
            $rendered['html'],
            $rendered['text'],
            $options['reply_to'] ?? null,
            $options['reply_to_name'] ?? null,
            $options['files'] ?? [],
        ), $options['reservation_id'] ?? null);
    }

    /** Envoi immediat, quel que soit le reglage de file d'attente (email de test). */
    public function sendNow(?string $key, string $to, TemplatedMail $mail): EmailLog
    {
        $log = $this->createLog($key, $to, $mail, null, 'queued');
        $this->deliver($log, $mail);

        return $log->refresh();
    }

    public function send(?string $key, string $to, TemplatedMail $mail, ?int $reservationId = null): EmailLog
    {
        $log = $this->createLog($key, $to, $mail, $reservationId, 'queued');

        if ($this->mailSettings->useQueue()) {
            SendLoggedEmail::dispatch($log, $mail);

            return $log;
        }

        $this->deliver($log, $mail);

        return $log->refresh();
    }

    public function deliver(EmailLog $log, TemplatedMail $mail, bool $rethrow = false): void
    {
        try {
            Mail::to($log->recipient)->send($mail);
            $log->update(['status' => 'sent', 'error' => null, 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 2000)]);
            Log::error("Échec d'envoi d'email à {$log->recipient}", ['log_id' => $log->id, 'error' => $exception->getMessage()]);

            if ($rethrow) {
                throw $exception; // la file d'attente reessaiera plus tard
            }
        }
    }

    /** @return array<string, string> */
    public function siteVariables(): array
    {
        $contact = config('home.contact');

        return [
            'site_nom' => 'CLASS’AFFAIRE',
            'site_telephone' => $contact['phone'],
            'site_email' => $contact['email'],
            'site_url' => url('/'),
        ];
    }

    private function createLog(?string $key, string $to, TemplatedMail $mail, ?int $reservationId, string $status): EmailLog
    {
        return EmailLog::create([
            'template_key' => $key,
            'recipient' => $to,
            'subject' => mb_substr($mail->mailSubject, 0, 255),
            'status' => $status,
            'reservation_id' => $reservationId,
        ]);
    }
}
