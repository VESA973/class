<?php

namespace App\Jobs;

use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Envoi en file d'attente (option "Envoyer via la file d'attente" de l'admin). */
class SendLoggedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public EmailLog $log, public TemplatedMail $mail)
    {
    }

    public function handle(EmailService $service): void
    {
        $service->deliver($this->log, $this->mail, rethrow: $this->attempts() < $this->tries);
    }
}
