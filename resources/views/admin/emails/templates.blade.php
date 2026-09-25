@extends('admin.layout')

@section('title', 'Modèles d’emails')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Communication</p>
            <h1>Emails</h1>
        </div>
    </div>

    @include('admin.emails._tabs')

    <div class="table-card">
        <table>
            <thead>
                <tr><th>Modèle</th><th>Objet</th><th>État</th><th>Envoyés</th><th>Modifié</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($templates as $template)
                    <tr>
                        <td><strong>{{ $template->name }}</strong><span>{{ $template->description }}</span></td>
                        <td>{{ $template->subject }}</td>
                        <td><span class="tag {{ $template->is_active ? 'tag-success' : 'tag-muted' }}">{{ $template->is_active ? 'Actif' : 'Désactivé' }}</span></td>
                        <td>{{ $counts[$template->key] ?? 0 }}</td>
                        <td>{{ $template->updated_at?->format('d/m/Y') }}</td>
                        <td><a href="{{ route('admin.emails.templates.edit', $template) }}">Modifier</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
