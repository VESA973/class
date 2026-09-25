<nav class="tabs" aria-label="Sections des emails">
    <a href="{{ route('admin.emails.settings') }}" @if (request()->routeIs('admin.emails.settings')) aria-current="page" @endif>Configuration</a>
    <a href="{{ route('admin.emails.templates') }}" @if (request()->routeIs('admin.emails.templates*')) aria-current="page" @endif>Modèles</a>
    <a href="{{ route('admin.emails.logs') }}" @if (request()->routeIs('admin.emails.logs')) aria-current="page" @endif>Historique</a>
</nav>
