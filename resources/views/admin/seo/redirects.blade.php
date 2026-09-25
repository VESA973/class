@extends('admin.layout')

@section('title', 'Redirections')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Site</p>
            <h1>SEO</h1>
        </div>
    </div>

    @include('admin.seo._tabs')

    <form method="POST" action="{{ route('admin.seo.redirects.store') }}" class="form-card" style="margin-bottom:20px">
        @csrf
        <h2>Nouvelle redirection</h2>
        <div class="redirect-grid">
            <label>Ancienne adresse<input name="from_path" value="{{ old('from_path') }}" placeholder="/ancienne-page" required></label>
            <label>Nouvelle adresse<input name="to_url" value="{{ old('to_url') }}" placeholder="/nouvelle-page ou https://…" required></label>
            <label>Type
                <select name="status_code">
                    <option value="301">301 — définitive</option>
                    <option value="302">302 — temporaire</option>
                </select>
            </label>
            <button class="btn" type="submit">Ajouter</button>
        </div>
        <p class="form-hint">Les changements d’adresse des véhicules créent automatiquement leur redirection 301.</p>
    </form>

    <div class="table-card">
        <table>
            <thead><tr><th>Ancienne adresse</th><th>Nouvelle adresse</th><th>Type</th><th>Visites</th><th></th></tr></thead>
            <tbody>
                @forelse ($redirects as $redirect)
                    <tr>
                        <td><code>{{ $redirect->from_path }}</code></td>
                        <td><code>{{ $redirect->to_url }}</code></td>
                        <td>{{ $redirect->status_code }}</td>
                        <td>{{ $redirect->hits }}@if ($redirect->last_hit_at)<span>Dernière : {{ $redirect->last_hit_at->timezone('Europe/Paris')->format('d/m/Y H:i') }}</span>@endif</td>
                        <td class="actions">
                            <form method="POST" action="{{ route('admin.seo.redirects.destroy', $redirect) }}" onsubmit="return confirm('Supprimer cette redirection ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Aucune redirection.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $redirects->links() }}
@endsection
