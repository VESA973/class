@extends('admin.layout')

@section('title', "Photo d'accueil")

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Page d'accueil</p>
            <h1>Photo d'accueil</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('home') }}" target="_blank">Voir l'accueil</a>
    </div>

    <form class="form-card compact" method="POST" action="{{ route('admin.hero.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div>
            <p class="field-note">{{ $settings->hero_image_url ? 'Photo actuelle' : 'Photo par défaut' }}</p>
            <img class="hero-preview" src="{{ $settings->hero_image_url ?? 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=1200&q=80' }}" alt="Photo d'accueil" data-hero-preview>
        </div>

        <label>
            Nouvelle photo
            <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" required data-hero-input>
            <span class="field-note">Format paysage conseillé, au moins 1920 × 1080 px. JPG, PNG ou WebP.</span>
        </label>

        <button class="btn" type="submit">Enregistrer la photo</button>
    </form>

    @if ($settings->hero_image_url)
        <form class="form-card compact danger-zone" method="POST" action="{{ route('admin.hero.destroy') }}">
            @csrf
            @method('DELETE')
            <p>Supprimer la photo actuelle et revenir à la photo par défaut.</p>
            <button class="btn btn-secondary" type="submit">Rétablir la photo par défaut</button>
        </form>
    @endif

    <script>
        const heroInput = document.querySelector('[data-hero-input]');
        const heroPreview = document.querySelector('[data-hero-preview]');

        if (heroInput && heroPreview) {
            heroInput.addEventListener('change', () => {
                const [file] = heroInput.files;

                if (file) {
                    heroPreview.src = URL.createObjectURL(file);
                }
            });
        }
    </script>
@endsection
