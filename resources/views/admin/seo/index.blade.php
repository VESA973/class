@extends('admin.layout')

@section('title', 'SEO')

@php
    $badge = fn ($meta) => $meta && ($meta->title || $meta->description || $meta->og_image_path)
        ? '<span class="tag tag-success">Personnalisé</span>'
        : '<span class="tag tag-muted">Automatique</span>';
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Site</p>
            <h1>SEO</h1>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('sitemap') }}" target="_blank" rel="noopener">sitemap.xml</a>
            <a class="btn btn-secondary" href="{{ route('robots') }}" target="_blank" rel="noopener">robots.txt</a>
        </div>
    </div>

    @include('admin.seo._tabs')

    <p class="form-hint" style="margin-bottom:14px">Sans personnalisation, chaque page utilise un titre et une description générés automatiquement. Personnalisez les pages importantes pour Google et les réseaux sociaux.</p>

    <div class="table-card" style="margin-bottom:20px">
        <table>
            <thead><tr><th>Page</th><th>Adresse</th><th>Balises</th><th>Indexation</th><th></th></tr></thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td><strong>{{ $page['name'] }}</strong><span>{{ $page['meta']?->title ?: 'Titre automatique' }}</span></td>
                        <td>{{ parse_url($page['url'], PHP_URL_PATH) ?: '/' }}</td>
                        <td>{!! $badge($page['meta']) !!}</td>
                        <td>{!! $page['meta']?->noindex ? '<span class="tag tag-danger">noindex</span>' : '<span class="tag">Indexée</span>' !!}</td>
                        <td><a href="{{ route('admin.seo.pages.edit', $page['key']) }}">Modifier</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-card" style="margin-bottom:20px">
        <table>
            <thead><tr><th>Véhicule</th><th>Adresse</th><th>Balises</th><th>Indexation</th><th></th></tr></thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    @php($meta = $vehicleMetas[$vehicle->id] ?? null)
                    <tr>
                        <td><strong>{{ $vehicle->name }}</strong>@unless ($vehicle->is_available)<span>Masqué sur le site</span>@endunless</td>
                        <td>/vehicules/{{ $vehicle->slug }}</td>
                        <td>{!! $badge($meta) !!}</td>
                        <td>{!! $meta?->noindex ? '<span class="tag tag-danger">noindex</span>' : '<span class="tag">Indexée</span>' !!}</td>
                        <td><a href="{{ route('admin.seo.vehicles.edit', $vehicle) }}">Modifier</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Aucun véhicule.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-card">
        <table>
            <thead><tr><th>Page légale</th><th>Adresse</th><th>Balises</th><th>Indexation</th><th></th></tr></thead>
            <tbody>
                @foreach ($legalPages as $legalPage)
                    @php($meta = $legalMetas[$legalPage->id] ?? null)
                    <tr>
                        <td><strong>{{ $legalPage->title }}</strong>@unless ($legalPage->is_published)<span>Masquée</span>@endunless</td>
                        <td>/{{ $legalPage->slug }}</td>
                        <td>{!! $badge($meta) !!}</td>
                        <td>{!! $meta?->noindex ? '<span class="tag tag-danger">noindex</span>' : '<span class="tag">Indexée</span>' !!}</td>
                        <td><a href="{{ route('admin.seo.legal.edit', $legalPage) }}">Modifier</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
