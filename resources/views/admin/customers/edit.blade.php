@extends('admin.layout')

@php($isNew = ! $customer->exists)

@section('title', $isNew ? 'Nouveau client' : 'Client '.$customer->display_name)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Clients @unless ($isNew)<span class="tag tag-muted">{{ \App\Models\Customer::SOURCES[$customer->source] ?? $customer->source }}</span>@endunless</p>
            <h1>{{ $isNew ? 'Nouveau client' : $customer->display_name }}</h1>
            @unless ($isNew)
                <p class="form-hint">Fiche créée le {{ $customer->created_at->timezone(config('app.local_timezone'))->format('d/m/Y') }}</p>
            @endunless
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.customers.index') }}">Retour</a>
            @unless ($isNew)
                <a class="btn" href="{{ route('admin.quotes.create', ['client' => $customer->id]) }}">+ Créer un devis</a>
            @endunless
        </div>
    </div>

    @if (session('duplicate_warning'))
        <div class="maintenance-banner" role="alert">
            <span>{{ session('duplicate_warning') }}</span>
            <a href="{{ route('admin.customers.duplicates') }}">Voir les doublons</a>
        </div>
    @elseif ($similar->isNotEmpty())
        <div class="maintenance-banner" role="note">
            <span>Fiche(s) ressemblante(s) :
                @foreach ($similar as $other)
                    <a href="{{ route('admin.customers.edit', $other) }}">{{ $other->display_name }}</a>@if (! $loop->last), @endif
                @endforeach
            </span>
            <a href="{{ route('admin.customers.duplicates') }}">Fusionner</a>
        </div>
    @endif

    <div class="editor-grid">
        <form class="form-card" method="POST" action="{{ $isNew ? route('admin.customers.store') : route('admin.customers.update', $customer) }}">
            @csrf
            @unless ($isNew)
                @method('PUT')
            @endunless

            <h2>Identité</h2>
            <div class="form-grid">
                <label>Type
                    <select name="type">
                        @foreach (\App\Models\Customer::TYPES as $type => $label)
                            <option value="{{ $type }}" @selected(old('type', $customer->type) === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Raison sociale<input name="company_name" maxlength="150" value="{{ old('company_name', $customer->company_name) }}" placeholder="Pour un professionnel"></label>
                <label>Prénom<input name="first_name" maxlength="100" value="{{ old('first_name', $customer->first_name) }}" autocomplete="off"></label>
                <label>Nom<input name="last_name" maxlength="100" value="{{ old('last_name', $customer->last_name) }}" autocomplete="off"></label>
                <label>SIRET<input name="siret" maxlength="30" value="{{ old('siret', $customer->siret) }}"></label>
                <label>Date de naissance<input type="date" name="birth_date" value="{{ old('birth_date', $customer->birth_date?->format('Y-m-d')) }}"></label>
            </div>

            <h2>Contact</h2>
            <div class="form-grid">
                <label>Email<input type="email" name="email" maxlength="160" value="{{ old('email', $customer->email) }}"></label>
                <label>Téléphone portable<input type="tel" name="phone_mobile" maxlength="40" value="{{ old('phone_mobile', $customer->phone_mobile) }}" placeholder="06 94 …"></label>
                <label>Téléphone fixe<input type="tel" name="phone_landline" maxlength="40" value="{{ old('phone_landline', $customer->phone_landline) }}" placeholder="05 94 …"></label>
            </div>
            <label>Adresse<input name="address" maxlength="255" value="{{ old('address', $customer->address) }}"></label>
            <div class="form-grid">
                <label>Code postal<input name="postal_code" maxlength="20" value="{{ old('postal_code', $customer->postal_code) }}"></label>
                <label>Ville<input name="city" maxlength="120" value="{{ old('city', $customer->city) }}"></label>
                <label>Pays<input name="country" maxlength="80" value="{{ old('country', $customer->country) }}" placeholder="Guyane française"></label>
            </div>

            <h2>Permis de conduire</h2>
            <p class="form-hint">Utile pour une location sans chauffeur.</p>
            <div class="form-grid">
                <label>N° de permis<input name="license_number" maxlength="50" value="{{ old('license_number', $customer->license_number) }}"></label>
                <label>Délivré le<input type="date" name="license_issued_at" value="{{ old('license_issued_at', $customer->license_issued_at?->format('Y-m-d')) }}"></label>
            </div>

            <label>Notes internes<textarea name="notes" rows="4" maxlength="5000">{{ old('notes', $customer->notes) }}</textarea></label>

            <div class="form-actions">
                @unless ($isNew)
                    <button class="btn btn-danger" type="submit" form="delete-customer">Supprimer</button>
                @endunless
                <button class="btn" type="submit">{{ $isNew ? 'Créer le client' : 'Enregistrer' }}</button>
            </div>
        </form>

        <div class="grid-stack">
            @if ($isNew)
                <section class="form-card">
                    <h2>Doublons</h2>
                    <p class="form-hint">L’enregistrement n’est jamais bloqué : si une fiche ressemblante existe (même email, téléphone ou nom), un avertissement s’affiche et vous pourrez fusionner les fiches dans « Doublons ».</p>
                </section>
            @else
                <section class="form-card" aria-labelledby="history-reservations">
                    <h2 id="history-reservations">Demandes ({{ $customer->reservations->count() }})</h2>
                    @forelse ($customer->reservations as $reservation)
                        <p><a href="{{ route('admin.reservations.show', $reservation) }}"><strong>n°{{ $reservation->id }}</strong></a> · {{ $reservation->vehicle?->name ?? 'Véhicule supprimé' }} · {{ $reservation->start_at?->format('d/m/Y') }} <span class="tag tag-{{ $reservation->status }}">{{ $reservation->status_label }}</span></p>
                    @empty
                        <p class="form-hint">Aucune demande.</p>
                    @endforelse
                </section>
                <section class="form-card" aria-labelledby="history-quotes">
                    <h2 id="history-quotes">Devis ({{ $customer->quotes->count() }})</h2>
                    @forelse ($customer->quotes as $quote)
                        <p><a href="{{ route('admin.quotes.edit', $quote) }}"><strong>{{ $quote->number }}</strong></a> · {{ $quote->issued_at->format('d/m/Y') }} · {{ \App\Models\Quote::money($quote->total_ttc) }} <span class="tag {{ $quote->status_class }}">{{ $quote->status_label }}</span></p>
                    @empty
                        <p class="form-hint">Aucun devis.</p>
                    @endforelse
                </section>
            @endif
        </div>
    </div>

    @unless ($isNew)
        <form id="delete-customer" method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Supprimer ce client de la base ? Ses demandes et devis sont conservés.')">
            @csrf
            @method('DELETE')
        </form>
    @endunless
@endsection
