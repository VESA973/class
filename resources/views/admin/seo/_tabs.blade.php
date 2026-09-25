<nav class="tabs" aria-label="Sections SEO">
    <a href="{{ route('admin.seo.index') }}" @if (request()->routeIs('admin.seo.index', 'admin.seo.pages.*', 'admin.seo.vehicles.*', 'admin.seo.legal.*')) aria-current="page" @endif>Pages & véhicules</a>
    <a href="{{ route('admin.seo.settings') }}" @if (request()->routeIs('admin.seo.settings')) aria-current="page" @endif>Réglages & robots.txt</a>
    <a href="{{ route('admin.seo.redirects') }}" @if (request()->routeIs('admin.seo.redirects')) aria-current="page" @endif>Redirections</a>
</nav>
