@extends('admin.layout')

@section('title', 'Pages légales')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Site</p>
            <h1>Pages légales</h1>
        </div>
    </div>

    @include('admin.legal._tabs')
    @include('admin.legal._missing')

    <p class="form-hint" style="margin-bottom:14px">Modèles conformes au droit français (LCEN, RGPD, Code de la consommation) à relire et compléter. Nous vous conseillons de les faire valider par un professionnel du droit. Les pages publiées apparaissent automatiquement dans le pied de page du site.</p>

    <div class="table-card">
        <table>
            <thead><tr><th>Page</th><th>Adresse</th><th>État</th><th>Dernière mise à jour</th><th>Versions</th><th></th></tr></thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td><strong>{{ $page->title }}</strong></td>
                        <td><a href="{{ $page->url }}" target="_blank" rel="noopener">/{{ $page->slug }}</a></td>
                        <td><span class="tag {{ $page->is_published ? 'tag-success' : 'tag-muted' }}">{{ $page->is_published ? 'Publiée' : 'Masquée' }}</span></td>
                        <td>{{ $page->updated_at->timezone(config('app.local_timezone'))->format('d/m/Y H:i') }}</td>
                        <td>{{ $page->versions_count }}</td>
                        <td class="actions"><a href="{{ route('admin.legal.edit', $page) }}">Modifier</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
