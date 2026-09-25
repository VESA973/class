<nav class="tabs" aria-label="Sections des devis">
    <a href="{{ route('admin.quotes.index') }}" @if (request()->routeIs('admin.quotes.index', 'admin.quotes.edit')) aria-current="page" @endif>Devis</a>
    <a href="{{ route('admin.quotes.settings') }}" @if (request()->routeIs('admin.quotes.settings')) aria-current="page" @endif>Réglages</a>
</nav>
