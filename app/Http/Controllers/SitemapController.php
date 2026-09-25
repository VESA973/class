<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Models\SeoMeta;
use App\Models\Vehicle;
use App\Services\Seo;
use Illuminate\Http\Response;

/** /sitemap.xml et /robots.txt generes automatiquement (Admin > SEO). */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $noindexPages = SeoMeta::query()->where('noindex', true)->whereNotNull('page_key')->pluck('page_key')->all();
        $urls = [];

        foreach (Seo::PAGES as $key => [, $route]) {
            if (! in_array('page:'.$key, $noindexPages, true)) {
                $urls[] = ['loc' => route($route), 'lastmod' => null, 'priority' => $key === 'home' ? '1.0' : '0.8'];
            }
        }

        $noindexVehicles = SeoMeta::query()->where('noindex', true)->where('seoable_type', (new Vehicle)->getMorphClass())->pluck('seoable_id')->all();

        Vehicle::query()->where('is_available', true)->whereNotNull('slug')->whereNotIn('id', $noindexVehicles)->orderBy('name')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Vehicle $vehicle) use (&$urls) {
                $urls[] = ['loc' => route('vehicles.show', $vehicle), 'lastmod' => $vehicle->updated_at?->toAtomString(), 'priority' => '0.7'];
            });

        $noindexLegal = SeoMeta::query()->where('noindex', true)->where('seoable_type', (new LegalPage)->getMorphClass())->pluck('seoable_id')->all();

        LegalPage::query()->where('is_published', true)->whereNotIn('id', $noindexLegal)->orderBy('position')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (LegalPage $page) use (&$urls) {
                $urls[] = ['loc' => $page->url, 'lastmod' => $page->updated_at?->toAtomString(), 'priority' => '0.3'];
            });

        return response()->view('seo.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(Seo $seo): Response
    {
        $content = rtrim($seo->defaults()['robots_txt'])."\n\nSitemap: ".route('sitemap')."\n";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
