<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

/**
 * Configuration d'envoi des emails reglee depuis l'admin (Admin > Emails > Configuration).
 *
 * Mode "env" (defaut) : les reglages MAIL_* du fichier .env s'appliquent, rien ne change.
 * Mode "smtp" : le serveur SMTP saisi dans l'admin remplace celui du .env.
 * Le mot de passe SMTP est chiffre avec la cle de l'application (APP_KEY) avant d'etre stocke.
 */
class MailSettings
{
    public const ENCRYPTIONS = ['tls' => 'STARTTLS (port 587)', 'ssl' => 'SSL/TLS (port 465)', 'none' => 'Aucun (déconseillé)'];

    public function __construct(private readonly Settings $settings)
    {
    }

    public function mode(): string
    {
        return $this->settings->get('mail.mode', 'env') === 'smtp' ? 'smtp' : 'env';
    }

    /** @return array<string, mixed> */
    public function values(): array
    {
        return [
            'mode' => $this->mode(),
            'host' => $this->settings->get('mail.smtp.host', ''),
            'port' => $this->settings->get('mail.smtp.port', 587),
            'encryption' => $this->settings->get('mail.smtp.encryption', 'tls'),
            'username' => $this->settings->get('mail.smtp.username', ''),
            'has_password' => (bool) $this->settings->get('mail.smtp.password'),
            'from_address' => $this->settings->get('mail.from.address') ?: config('mail.from.address'),
            'from_name' => $this->settings->get('mail.from.name') ?: config('mail.from.name'),
            'admin_email' => $this->adminEmail(),
            'use_queue' => $this->useQueue(),
        ];
    }

    public function adminEmail(): ?string
    {
        return $this->settings->get('mail.admin_email') ?: config('booking.admin_email');
    }

    public function useQueue(): bool
    {
        return (bool) $this->settings->get('mail.use_queue', false);
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): void
    {
        $values = [
            'mail.mode' => $data['mode'],
            'mail.smtp.host' => $data['host'] ?? '',
            'mail.smtp.port' => (int) ($data['port'] ?? 587),
            'mail.smtp.encryption' => $data['encryption'] ?? 'tls',
            'mail.smtp.username' => $data['username'] ?? '',
            'mail.from.address' => $data['from_address'] ?? '',
            'mail.from.name' => $data['from_name'] ?? '',
            'mail.admin_email' => $data['admin_email'] ?? '',
            'mail.use_queue' => (bool) ($data['use_queue'] ?? false),
        ];

        // Mot de passe : vide = on garde l'actuel ; case "effacer" = on le supprime.
        if (! empty($data['clear_password'])) {
            $values['mail.smtp.password'] = null;
        } elseif (! empty($data['password'])) {
            $values['mail.smtp.password'] = Crypt::encryptString($data['password']);
        }

        $this->settings->set($values);
        $this->apply();
    }

    /** Applique la configuration a Laravel (appele au demarrage de chaque requete et apres enregistrement). */
    public function apply(): void
    {
        // Mailer d'origine (.env), memorise avant toute surcharge : affiche dans l'admin.
        if (config('mail.env_default') === null) {
            config(['mail.env_default' => config('mail.default')]);
        }

        $fromAddress = $this->settings->get('mail.from.address');
        $fromName = $this->settings->get('mail.from.name');

        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }

        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }

        if ($this->mode() === 'smtp' && $this->settings->get('mail.smtp.host')) {
            $encryption = $this->settings->get('mail.smtp.encryption', 'tls');

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), [
                    'transport' => 'smtp',
                    'url' => null,
                    'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                    'host' => $this->settings->get('mail.smtp.host'),
                    'port' => (int) $this->settings->get('mail.smtp.port', 587),
                    'username' => $this->settings->get('mail.smtp.username') ?: null,
                    'password' => $this->password(),
                    'timeout' => 15,
                    // STARTTLS obligatoire en "tls", desactive en "none".
                    'require_tls' => $encryption === 'tls',
                    'auto_tls' => $encryption !== 'none',
                ]),
            ]);
        }

        // Les mailers deja crees gardent l'ancienne configuration : on les oublie.
        Mail::purge();
    }

    private function password(): ?string
    {
        $encrypted = $this->settings->get('mail.smtp.password');

        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            // APP_KEY changee : le mot de passe doit etre ressaisi dans l'admin.
            return null;
        }
    }
}
