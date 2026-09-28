@extends('admin.layout')

@section('title', 'Doublons clients')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & Devis</p>
            <h1>Clients</h1>
        </div>
    </div>

    @php($duplicateCount = array_sum(array_map(fn ($group) => $group['customers']->count(), $groups)))
    @include('admin.customers._tabs')

    <p class="form-hint" style="margin-bottom:14px">
        Fiches qui partagent un email, un numéro de téléphone (quel que soit le format), le même prénom et nom ou la même raison sociale.
        Choisissez la fiche à <strong>conserver</strong>, cochez celles à <strong>fusionner</strong> : les champs vides de la fiche conservée sont complétés,
        les notes regroupées, les demandes et devis rattachés ; les autres fiches sont archivées. Deux personnes différentes ? Ne cochez rien.
    </p>

    @error('merge')<div class="flash flash-error">{{ $message }}</div>@enderror

    @forelse ($groups as $index => $group)
        <form method="POST" action="{{ route('admin.customers.merge') }}" class="form-card duplicate-group" onsubmit="return confirm('Fusionner les fiches cochées dans la fiche conservée ?')">
            @csrf
            <div class="section-head">
                <h2>Groupe {{ $index + 1 }}</h2>
                <span class="tag tag-pending">{{ implode(' · ', $group['reasons']) }}</span>
            </div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr><th>Conserver</th><th>Fusionner</th><th>Client</th><th>Contact</th><th>Demandes / devis</th><th>Créée le</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($group['customers'] as $customer)
                            <tr>
                                <td><input type="radio" name="primary" value="{{ $customer->id }}" @checked($loop->first) aria-label="Conserver {{ $customer->display_name }}" data-primary></td>
                                <td><input type="checkbox" name="merge[]" value="{{ $customer->id }}" @checked(! $loop->first) @disabled($loop->first) aria-label="Fusionner {{ $customer->display_name }}" data-merge></td>
                                <td><a href="{{ route('admin.customers.edit', $customer) }}" target="_blank" rel="noopener"><strong>{{ $customer->display_name }}</strong></a><span>{{ \App\Models\Customer::SOURCES[$customer->source] ?? $customer->source }}</span></td>
                                <td>{{ $customer->email ?: '—' }}<span>{{ collect([$customer->phone_mobile, $customer->phone_landline])->filter()->implode(' · ') }}</span></td>
                                <td>{{ $customer->reservations_count }} / {{ $customer->quotes_count }}</td>
                                <td>{{ $customer->created_at->timezone(config('app.local_timezone'))->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions"><button class="btn" type="submit">Fusionner les fiches cochées</button></div>
        </form>
    @empty
        <div class="form-card"><p class="empty-state" style="padding:12px 0">Aucun doublon détecté.</p></div>
    @endforelse
@endsection

@push('scripts')
    <script>
        // La fiche conservee ne peut pas etre fusionnee dans elle-meme.
        document.querySelectorAll('.duplicate-group').forEach(function (group) {
            group.addEventListener('change', function (event) {
                if (!event.target.matches('[data-primary]')) return;
                group.querySelectorAll('tbody tr').forEach(function (row) {
                    var isPrimary = row.querySelector('[data-primary]').checked;
                    var merge = row.querySelector('[data-merge]');
                    merge.disabled = isPrimary;
                    if (isPrimary) merge.checked = false;
                });
            });
        });
    </script>
@endpush
