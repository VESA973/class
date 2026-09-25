<nav class="tabs" aria-label="Sections cookies">
    <a href="{{ route('admin.cookies.edit') }}" @if (request()->routeIs('admin.cookies.edit')) aria-current="page" @endif>Bandeau & catégories</a>
    <a href="{{ route('admin.cookies.registry') }}" @if (request()->routeIs('admin.cookies.registry')) aria-current="page" @endif>Registre des consentements</a>
</nav>
