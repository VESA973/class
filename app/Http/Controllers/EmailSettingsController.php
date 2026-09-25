<?php

namespace App\Http\Controllers;

use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Services\EmailService;
use App\Services\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin > Emails > Configuration (SMTP, expediteur, destinataire admin, email de test). */
class EmailSettingsController extends Controller
{
    public function __construct(private readonly MailSettings $mailSettings)
    {
    }

    public function edit(): View
    {
        return view('admin.emails.settings', [
            'values' => $this->mailSettings->values(),
            'encryptions' => MailSettings::ENCRYPTIONS,
            'envMailer' => config('mail.env_default', config('mail.default')),
            'stats' => [
                'sent' => EmailLog::where('status', 'sent')->where('created_at', '>=', now()->subDays(30))->count(),
                'failed' => EmailLog::where('status', 'failed')->where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $smtp = $request->input('mode') === 'smtp';

        $data = $request->validate([
            'mode' => ['required', Rule::in(['env', 'smtp'])],
            'host' => [Rule::requiredIf($smtp), 'nullable', 'string', 'max:255'],
            'port' => [Rule::requiredIf($smtp), 'nullable', 'integer', 'between:1,65535'],
            'encryption' => ['required', Rule::in(array_keys(MailSettings::ENCRYPTIONS))],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:100'],
            'admin_email' => ['nullable', 'email', 'max:255'],
        ], [
            'host.required' => 'Indiquez le serveur SMTP.',
            'port.required' => 'Indiquez le port.',
            'from_address.required' => "Indiquez l'adresse d'expédition.",
            'from_address.email' => "L'adresse d'expédition est invalide.",
            'admin_email.email' => "L'adresse administrateur est invalide.",
        ]);

        $data['use_queue'] = $request->boolean('use_queue');
        $data['clear_password'] = $request->boolean('clear_password');
        $this->mailSettings->save($data);

        return redirect()->route('admin.emails.settings')->with('status', 'Configuration des emails enregistrée. Pensez à envoyer un email de test.');
    }

    public function test(Request $request, EmailService $emails): RedirectResponse
    {
        $data = $request->validate(['to' => ['required', 'email', 'max:255']], ['to.email' => 'Adresse de test invalide.']);

        $html = '<h1>Email de test</h1><p>Si vous lisez ce message, les emails du site sont correctement configurés.</p>'
            .'<ul><li><strong>Envoyé le :</strong> '.e(now('Europe/Paris')->format('d/m/Y à H:i')).'</li>'
            .'<li><strong>Mode :</strong> '.e($this->mailSettings->mode() === 'smtp' ? 'serveur SMTP de l’admin' : 'réglages du fichier .env').'</li></ul>';

        $log = $emails->sendNow('test', $data['to'], new TemplatedMail('Email de test - CLASS’AFFAIRE', $html, "Email de test\n\nLes emails du site sont correctement configurés."));

        return $log->status === 'sent'
            ? back()->with('status', "Email de test envoyé à {$data['to']}. Vérifiez la boîte de réception (et les indésirables).")
            : back()->withInput()->withErrors(['to' => "Échec de l'envoi : ".$log->error]);
    }
}
