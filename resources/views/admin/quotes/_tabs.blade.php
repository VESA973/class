<nav class="tabs" aria-label="Sections des devis">
    <a href="{{ route('admin.quotes.index') }}" @if (request()->routeIs('admin.quotes.index', 'admin.quotes.edit', 'admin.quotes.create')) aria-current="page" @endif>Devis</a>
    <a href="{{ route('admin.quotes.services') }}" @if (request()->routeIs('admin.quotes.services')) aria-current="page" @endif>Services & forfaits</a>
    <a href="{{ route('admin.quotes.settings') }}" @if (request()->routeIs('admin.quotes.settings')) aria-current="page" @endif>Réglages</a>
</nav>
