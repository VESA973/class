<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use App\Services\CookieSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Enregistrement d'un choix cookies dans le registre (appele par le bandeau). */
class CookieConsentController extends Controller
{
    public function store(Request $request, CookieSettings $cookies): JsonResponse
    {
        $data = $request->validate([
            'consent_id' => ['required', 'uuid'],
            'action' => ['required', Rule::in(array_keys(CookieConsent::ACTIONS))],
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
        ]);

        CookieConsent::create($data + ['policy_version' => $cookies->all()['version']]);

        return response()->json(['recorded' => true], 201);
    }
}
