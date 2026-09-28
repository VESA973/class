<nav class="tabs" aria-label="Sections des clients">
    <a href="{{ route('admin.customers.index') }}" @if (request()->routeIs('admin.customers.index', 'admin.customers.edit', 'admin.customers.create')) aria-current="page" @endif>Clients</a>
    <a href="{{ route('admin.customers.duplicates') }}" @if (request()->routeIs('admin.customers.duplicates')) aria-current="page" @endif>Doublons{{ ($duplicateCount ?? 0) ? ' ('.$duplicateCount.')' : '' }}</a>
</nav>
