@if (count($missing))
    <div class="maintenance-banner" role="note">
        <span>{{ count($missing) }} information(s) à compléter (affichées « [À compléter] » sur le site) : {{ implode(', ', array_slice($missing, 0, 6)) }}{{ count($missing) > 6 ? '…' : '' }}</span>
        <a href="{{ route('admin.legal.info') }}">Compléter</a>
    </div>
@endif
