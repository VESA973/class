<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create-user', function () {
    $name = $this->ask('Nom');
    $email = $this->ask('Email');
    $password = $this->secret('Mot de passe (8 caracteres minimum)');

    $validator = Validator::make(compact('name', 'email', 'password'), [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    User::create(compact('name', 'email', 'password'));

    $this->info("Utilisateur {$email} cree.");
})->purpose('Creer un utilisateur pour acceder a l\'administration');

// Suppression automatique des preuves de consentement cookies de plus de 13 mois.
// Necessite la tache planifiee du serveur : * * * * * php artisan schedule:run
Schedule::command('model:prune', ['--model' => [\App\Models\CookieConsent::class]])->daily();
