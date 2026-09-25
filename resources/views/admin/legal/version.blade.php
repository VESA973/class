@extends('admin.layout')

@section('title', 'Version - '.$page->title)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Version du {{ $version->created_at->timezone(config('app.local_timezone'))->format('d/m/Y à H:i') }} · {{ $version->user?->name ?? 'Système' }}</p>
            <h1>{{ $version->title }}</h1>
        </div>
        <div class="form-actions">
            <form method="POST" action="{{ route('admin.legal.restore', [$page, $version]) }}" onsubmit="return confirm('Restaurer cette version ?')">@csrf<button class="btn" type="submit">Restaurer cette version</button></form>
            <a class="btn btn-secondary" href="{{ route('admin.legal.edit', $page) }}">Retour</a>
        </div>
    </div>
    <article class="form-card legal-preview">{!! $html !!}</article>
@endsection
