<?php

namespace App\Services;

use App\Models\SeoMeta;
use App\Models\SiteSetting;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Balises SEO de chaque page publique : valeurs saisies dans l'admin (SEO), sinon valeurs par defaut
 * generees automatiquement (titre et description de la page, photo du vehicule, image par defaut...).
 */
class Seo
{
    /**
     * Pages fixes du site : cle => [nom, route, titre par defaut, description par defaut].
     * Leur adresse ne change pas (les liens existants et le referencement restent valides).
     */
    public const PAGES = [
        'home' => ['Accueil', 'home', 'CLASS’AFFAIRE - Location de voitures de prestige avec ou sans chauffeur', 'Location de voitures de prestige avec ou sans chauffeur à Paris, Cannes et Roissy : SUV, supercars et berlines, réservation en ligne et disponibilités en temps réel.'],
        'vehicles' => ['Véhicules', 'vehicles.page', 'Nos véhicules - CLASS’AFFAIRE', 'Découvrez la flotte CLASS’AFFAIRE : SUV, supercars, berlines et véhicules avec chauffeur, à découvrir en 3D.'],
        'prestations' => ['Prestations', 'prestations.page', 'Prestations avec chauffeur - CLASS’AFFAIRE', 'Mariages, transferts, soirées, évènements privés, voyages d’affaires : nos chauffeurs et leurs voitures à votre disposition.'],
        'contact' => ['Contact', 'contact.page', 'Contact - CLASS’AFFAIRE', 'Contactez CLASS’AFFAIRE 24h/24 et 7j/7 : téléphone, email, adresse à Roissy-en-France.'],
        'booking' => ['Réservation', 'booking.create', 'Réserver un véhicule - CLASS’AFFAIRE', 'Réservez votre véhicule de prestige en ligne : choisissez vos dates, vérifiez les disponibilités et envoyez votre demande.'],
    ];

    public static function pageTitle(string $key): string
    {
        return self::PAGES[$key][2];
    }

    public static function pageDescription(string $key): string
    {
        return self::PAGES[$key][3];
    }

    /** @return array{title: string, description: string} */
    public static function vehicleDefaults(Vehicle $vehicle): array
    {
        return [
            'title' => $vehicle->name.' - CLASS’AFFAIRE',
            'description' => 'Louez la '.$vehicle->name.' ('.$vehicle->category.') avec CLASS’AFFAIRE : découvrez-la en 3D, ses caractéristiques et ses disponibilités.',
        ];
    }

    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        $contact = config('home.contact');

        return [
            'site_name' => $this->settings->get('seo.site_name') ?: 'CLASS’AFFAIRE',
            'default_description' => $this->settings->get('seo.default_description') ?: 'Class’Affaire, location de voitures de prestige avec ou sans chauffeur à Paris, Cannes et Roissy depuis 2021.',
            'default_og_image' => $this->settings->get('seo.default_og_image'),
            'robots_txt' => $this->settings->get('seo.robots_txt') ?: "User-agent: *\nDisallow: /admin\n",
            'business' => array_merge([
                'type' => 'AutoRental',
                'name' => 'CLASS’AFFAIRE',
                'telephone' => $contact['phone'],
                'email' => $contact['email'],
                'street' => '174 Rue de la Belle Étoile',
                'postal_code' => '95700',
                'city' => 'Roissy-en-France',
                'area_served' => 'Paris, Cannes, Roissy',
                'price_range' => '€€€€',
                'opening_hours' => 'Mo-Su 00:00-23:59',
                'same_as' => '',
            ], (array) $this->settings->get('seo.business', [])),
        ];
    }

    public function metaFor(?string $pageKey, ?Model $model = null): ?SeoMeta
    {
        try {
            if ($model) {
                return SeoMeta::query()->where('seoable_type', $model->getMorphClass())->where('seoable_id', $model->getKey())->first();
            }

            return $pageKey && Schema::hasTable('seo_metas') ? SeoMeta::query()->where('page_key', $pageKey)->first() : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Balises finales d'une page.
     *
     * @return array{title: string, description: string, canonical: string, robots: string, image: ?string, type: string, site_name: string}
     */
    public function resolve(?string $pageKey, ?Model $model, string $defaultTitle, string $defaultDescription, ?string $defaultImage = null): array
    {
        $meta = $this->metaFor($pageKey, $model);
        $defaults = $this->defaults();

        $image = $meta?->og_image_url
            ?? $defaultImage
            ?? ($defaults['default_og_image'] ? Storage::disk('public')->url($defaults['default_og_image']) : null)
            ?? $this->heroImage();

        return [
            'title' => $meta?->title ?: ($defaultTitle ?: $defaults['site_name']),
            'description' => Str::limit($meta?->description ?: ($defaultDescription ?: $defaults['default_description']), 300, '…'),
            'canonical' => $meta?->canonical_url ?: url()->current(),
            'robots' => $meta?->noindex ? 'noindex, follow' : 'index, follow',
            'image' => $image ? (str_starts_with($image, 'http') ? $image : url($image)) : null,
            'type' => $model instanceof Vehicle ? 'product' : 'website',
            'site_name' => $defaults['site_name'],
        ];
    }

    /** Donnees structurees de l'entreprise (schema.org AutoRental / LocalBusiness). @return array<string, mixed> */
    public function businessSchema(): array
    {
        $business = $this->defaults()['business'];
        $logo = $this->safe(fn () => SiteSetting::current()->logo_url);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $business['type'] ?: 'AutoRental',
            '@id' => url('/').'#organization',
            'name' => $business['name'],
            'url' => url('/'),
            'logo' => $logo ? url($logo) : null,
            'image' => $this->heroImage(),
            'telephone' => $business['telephone'],
            'email' => $business['email'],
            'priceRange' => $business['price_range'],
            'openingHours' => $business['opening_hours'] ?: null,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $business['street'],
                'postalCode' => $business['postal_code'],
                'addressLocality' => $business['city'],
                'addressCountry' => config('home.contact.country_iso', 'FR'),
            ],
            'areaServed' => array_values(array_filter(array_map('trim', explode(',', (string) $business['area_served'])))),
            'sameAs' => array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $business['same_as'])))) ?: null,
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /** Vehicule : schema.org Car (produit loue a la journee) + fil d'Ariane. @return list<array<string, mixed>> */
    public function vehicleSchemas(Vehicle $vehicle): array
    {
        $image = $vehicle->display_image;

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Car',
                'name' => $vehicle->name,
                'brand' => ['@type' => 'Brand', 'name' => Str::before($vehicle->name, ' ')],
                'category' => $vehicle->category,
                'description' => $vehicle->description ?: null,
                'image' => str_starts_with($image, 'http') ? $image : url($image),
                'url' => route('vehicles.show', $vehicle),
                'fuelType' => $vehicle->fuel_type,
                'vehicleTransmission' => $vehicle->transmission,
                'vehicleSeatingCapacity' => $vehicle->seats,
                'vehicleEngine' => $vehicle->horsepower ? ['@type' => 'EngineSpecification', 'enginePower' => ['@type' => 'QuantitativeValue', 'value' => $vehicle->horsepower, 'unitCode' => 'BHP']] : null,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $vehicle->daily_price,
                    'priceCurrency' => 'EUR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => route('booking.create', ['vehicle' => $vehicle->id]),
                    'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => $vehicle->daily_price, 'priceCurrency' => 'EUR', 'unitText' => 'jour'],
                    'seller' => ['@id' => url('/').'#organization'],
                ],
            ], fn ($value) => $value !== null),
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Véhicules', 'item' => route('vehicles.page')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $vehicle->name, 'item' => route('vehicles.show', $vehicle)],
                ],
            ],
        ];
    }

    /** JSON-LD sans risque d'injection (</script> est echappe). */
    public static function jsonLd(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    private function heroImage(): ?string
    {
        $hero = $this->safe(fn () => SiteSetting::current()->hero_image_url);

        return $hero ? url($hero) : 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=1200&h=630&q=80';
    }

    private function safe(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return null;
        }
    }
}
