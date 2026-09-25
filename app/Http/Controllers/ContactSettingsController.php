<?php

namespace App\Http\Controllers;

use App\Services\ContactSettings;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Admin > Parametres > Coordonnees & WhatsApp. */
class ContactSettingsController extends Controller
{
    public function edit(ContactSettings $contact): View
    {
        return view('admin.contact.edit', ['values' => $contact->values(), 'whatsappUrl' => $contact->whatsappUrl()]);
    }

    public function update(Request $request, Settings $settings, ContactSettings $contact): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:40', 'regex:/^[+0-9 ().-]{6,40}$/'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9 ().-]*$/'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => 'Numéro de téléphone invalide.',
            'whatsapp_number.regex' => 'Numéro WhatsApp invalide.',
        ]);

        $whatsapp = ContactSettings::normalizeWhatsapp((string) ($data['whatsapp_number'] ?? ''));
        $enabled = $request->boolean('whatsapp_enabled');

        if ($enabled && (strlen($whatsapp) < 8 || strlen($whatsapp) > 15)) {
            throw ValidationException::withMessages(['whatsapp_number' => 'Indiquez un numéro WhatsApp complet (ex. +33 6 12 34 56 78) pour activer le bouton.']);
        }

        $settings->set([
            'contact.phone' => trim($data['phone']),
            'contact.email' => $data['email'],
            'contact.address' => $data['address'],
            'contact.whatsapp_number' => $whatsapp,
            'contact.whatsapp_enabled' => $enabled,
            'contact.whatsapp_message' => $data['whatsapp_message'] ?? '',
        ]);
        $contact->apply();

        return redirect()->route('admin.contact.edit')->with('status', 'Coordonnées enregistrées : elles sont à jour sur tout le site.');
    }
}
