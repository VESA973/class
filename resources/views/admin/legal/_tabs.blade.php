<nav class="tabs" aria-label="Sections des pages légales">
    <a href="{{ route('admin.legal.index') }}" @if (request()->routeIs('admin.legal.index', 'admin.legal.edit', 'admin.legal.version')) aria-current="page" @endif>Pages</a>
    <a href="{{ route('admin.legal.info') }}" @if (request()->routeIs('admin.legal.info')) aria-current="page" @endif>Informations de l’entreprise</a>
</nav>
