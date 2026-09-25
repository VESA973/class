@props(['vehicle', 'delay' => 0])
{{-- Carte vehicule (catalogue, vehicules similaires) : toute la carte mene a la fiche --}}
<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-card shadow-sm transition duration-300 hover:-translate-y-1 hover:border-white/20 hover:shadow-2xl hover:shadow-black/50 focus-within:ring-2 focus-within:ring-ring" data-reveal data-category="{{ $vehicle->category }}" style="--reveal-delay: {{ $delay }}ms">
    <div class="relative aspect-[16/10] overflow-hidden bg-muted">
        <x-icon name="car-front" class="absolute left-1/2 top-1/2 size-10 -translate-x-1/2 -translate-y-1/2 text-muted-foreground/60" />
        <img src="{{ $vehicle->display_image }}" alt="" loading="lazy" decoding="async" width="800" height="500" onerror="this.hidden = true" class="relative size-full object-cover transition duration-700 group-hover:scale-105">
        <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
        <span class="absolute left-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">{{ $vehicle->category }}</span>
        @if ($vehicle->model_path || $vehicle->video_url)
            <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-black">
                <x-icon name="rotate-3d" class="size-3.5" /> 3D
            </span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-semibold">
            <a href="{{ route('vehicles.show', $vehicle) }}" class="after:absolute after:inset-0 focus:outline-none">{{ $vehicle->name }}</a>
        </h3>
        <ul class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground" aria-label="Caractéristiques">
            @if ($vehicle->seats)
                <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="users" class="size-3.5" /> {{ $vehicle->seats }} places</li>
            @endif
            <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="settings-2" class="size-3.5" /> {{ $vehicle->transmission }}</li>
            <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="fuel" class="size-3.5" /> {{ $vehicle->fuel_type }}</li>
            @if ($vehicle->horsepower)
                <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="gauge" class="size-3.5" /> {{ $vehicle->horsepower }} ch</li>
            @endif
        </ul>
        <div class="mt-auto flex items-end justify-between gap-4 pt-6">
            <p class="text-sm text-muted-foreground">
                À partir de<br>
                <span class="text-2xl font-semibold text-foreground">{{ number_format($vehicle->daily_price, 0, ',', ' ') }} €</span> / jour
            </p>
            <span aria-hidden="true" class="inline-flex h-10 items-center gap-1.5 rounded-md border border-white/15 px-4 text-sm font-semibold transition group-hover:bg-primary group-hover:text-primary-foreground">
                Découvrir <x-icon name="arrow-right" class="size-4" />
            </span>
        </div>
    </div>
</article>
