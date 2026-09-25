<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Models\SeoMeta;
use App\Models\Vehicle;
use App\Services\Seo;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin > SEO : balises par page et par vehicule, reglages generaux, robots.txt. */
class SeoController extends Controller
{
    public function __construct(private readonly Seo $seo)
    {
    }

    public function index(): View
    {
        $pageMetas = SeoMeta::query()->whereNotNull('page_key')->get()->keyBy('page_key');
        $vehicleMetas = SeoMeta::query()->where('seoable_type', (new Vehicle)->getMorphClass())->get()->keyBy('seoable_id');

        return view('admin.seo.index', [
            'pages' => collect(Seo::PAGES)->map(fn ($page, $key) => [
                'key' => $key, 'name' => $page[0], 'url' => route($page[1]), 'meta' => $pageMetas['page:'.$key] ?? null,
            ]),
            'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'slug', 'is_available']),
            'vehicleMetas' => $vehicleMetas,
            'legalPages' => LegalPage::query()->orderBy('position')->get(['id', 'title', 'slug', 'is_published']),
            'legalMetas' => SeoMeta::query()->where('seoable_type', (new LegalPage)->getMorphClass())->get()->keyBy('seoable_id'),
        ]);
    }

    public function editPage(string $page): View
    {
        abort_unless(isset(Seo::PAGES[$page]), 404);
        [$name, $route, $title, $description] = Seo::PAGES[$page];

        return $this->form($name, route($route), $this->seo->metaFor('page:'.$page), $title, $description, route('admin.seo.pages.update', $page));
    }

    public function updatePage(Request $request, string $page): RedirectResponse
    {
        abort_unless(isset(Seo::PAGES[$page]), 404);
        $meta = SeoMeta::query()->firstOrNew(['page_key' => 'page:'.$page]);
        $this->save($request, $meta);

        return redirect()->route('admin.seo.pages.edit', $page)->with('status', 'Balises SEO enregistrées.');
    }

    public function editVehicle(Vehicle $vehicle): View
    {
        $defaults = Seo::vehicleDefaults($vehicle);

        return $this->form($vehicle->name, route('vehicles.show', $vehicle), $this->seo->metaFor(null, $vehicle), $defaults['title'], $defaults['description'],
            route('admin.seo.vehicles.update', $vehicle), ['value' => $vehicle->slug, 'prefix' => url('/vehicules').'/']);
    }

    public function updateVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('vehicles', 'slug')->ignore($vehicle)],
        ], [
            'slug.regex' => 'Adresse invalide : lettres minuscules, chiffres et tirets uniquement (ex. rolls-royce-ghost).',
            'slug.unique' => 'Cette adresse est déjà utilisée par un autre véhicule.',
        ]);

        $meta = SeoMeta::query()->firstOrNew(['seoable_type' => $vehicle->getMorphClass(), 'seoable_id' => $vehicle->id]);
        $this->save($request, $meta);

        $slugChanged = $vehicle->slug !== $data['slug'];
        $vehicle->update(['slug' => $data['slug']]); // l'ancienne adresse redirige automatiquement (301)

        return redirect()->route('admin.seo.vehicles.edit', $vehicle)->with('status', $slugChanged
            ? 'Balises enregistrées. Nouvelle adresse active : l’ancienne redirige automatiquement (301).'
            : 'Balises SEO enregistrées.');
    }

    public function editLegal(LegalPage $legalPage): View
    {
        return $this->form($legalPage->title, $legalPage->url, $this->seo->metaFor(null, $legalPage), $legalPage->title.' - CLASS’AFFAIRE',
            $legalPage->title.' du site CLASS’AFFAIRE, location de voitures de prestige avec ou sans chauffeur.',
            route('admin.seo.legal.update', $legalPage), ['value' => $legalPage->slug, 'prefix' => url('/').'/']);
    }

    public function updateLegal(Request $request, LegalPage $legalPage): RedirectResponse
    {
        $reserved = ['admin', 'vehicules', 'prestations', 'contact', 'reserver', 'api', 'storage', 'build', 'sitemap', 'robots', 'up'];
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn($reserved), Rule::unique('legal_pages', 'slug')->ignore($legalPage)],
        ], [
            'slug.regex' => 'Adresse invalide : lettres minuscules, chiffres et tirets uniquement.',
            'slug.not_in' => 'Cette adresse est réservée par le site.',
            'slug.unique' => 'Cette adresse est déjà utilisée.',
        ]);

        $this->save($request, SeoMeta::query()->firstOrNew(['seoable_type' => $legalPage->getMorphClass(), 'seoable_id' => $legalPage->id]));
        $legalPage->update(['slug' => $data['slug']]); // l'ancienne adresse redirige automatiquement (301)

        return redirect()->route('admin.seo.legal.edit', $legalPage)->with('status', 'Balises SEO enregistrées.');
    }

    public function settings(): View
    {
        return view('admin.seo.settings', ['defaults' => $this->seo->defaults()]);
    }

    public function updateSettings(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'default_description' => ['nullable', 'string', 'max:300'],
            'default_og_image' => ['nullable', 'image', 'max:4096'],
            'robots_txt' => ['nullable', 'string', 'max:5000'],
            'business.type' => ['required', Rule::in(['AutoRental', 'LocalBusiness', 'TaxiService', 'Organization'])],
            'business.name' => ['required', 'string', 'max:120'],
            'business.telephone' => ['nullable', 'string', 'max:40'],
            'business.email' => ['nullable', 'email', 'max:255'],
            'business.street' => ['nullable', 'string', 'max:255'],
            'business.postal_code' => ['nullable', 'string', 'max:10'],
            'business.city' => ['nullable', 'string', 'max:120'],
            'business.area_served' => ['nullable', 'string', 'max:255'],
            'business.price_range' => ['nullable', 'string', 'max:10'],
            'business.opening_hours' => ['nullable', 'string', 'max:120'],
            'business.same_as' => ['nullable', 'string', 'max:1000'],
        ]);

        $values = [
            'seo.site_name' => $data['site_name'],
            'seo.default_description' => $data['default_description'] ?? '',
            'seo.robots_txt' => $data['robots_txt'] ?? '',
            'seo.business' => array_map(fn ($value) => $value ?? '', $data['business']),
        ];

        if ($request->hasFile('default_og_image')) {
            if ($old = app(Settings::class)->get('seo.default_og_image')) {
                app(\App\Services\ImageOptimizer::class)->delete($old);
            }
            $values['seo.default_og_image'] = app(\App\Services\ImageOptimizer::class)->optimize($request->file('default_og_image')->store('seo', 'public'), 1200);
        }

        $settings->set($values);

        return redirect()->route('admin.seo.settings')->with('status', 'Réglages SEO enregistrés.');
    }

    /** @param array{value: string, prefix: string}|null $slug */
    public static function formView(string $name, string $url, ?SeoMeta $meta, string $defaultTitle, string $defaultDescription, string $action, ?array $slug = null): View
    {
        return view('admin.seo.edit', compact('name', 'url', 'meta', 'defaultTitle', 'defaultDescription', 'action', 'slug'));
    }

    /** @param array{value: string, prefix: string}|null $slug */
    private function form(string $name, string $url, ?SeoMeta $meta, string $defaultTitle, string $defaultDescription, string $action, ?array $slug = null): View
    {
        return self::formView($name, $url, $meta, $defaultTitle, $defaultDescription, $action, $slug);
    }

    /** Enregistre les balises communes a tous les elements (titre, description, image, canonical, noindex). */
    public static function save(Request $request, SeoMeta $meta): void
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'og_image' => ['nullable', 'image', 'max:4096'],
        ], [
            'canonical_url.url' => 'L’adresse canonique doit être une adresse complète (https://…).',
            'og_image.image' => 'L’image de partage doit être une image (JPG, PNG, WebP).',
        ]);

        $meta->fill([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'noindex' => $request->boolean('noindex'),
        ]);

        if ($request->boolean('remove_og_image') || $request->hasFile('og_image')) {
            if ($meta->og_image_path) {
                app(\App\Services\ImageOptimizer::class)->delete($meta->og_image_path);
            }
            $meta->og_image_path = $request->hasFile('og_image')
                ? app(\App\Services\ImageOptimizer::class)->optimize($request->file('og_image')->store('seo', 'public'), 1200)
                : null;
        }

        $meta->save();
    }
}
