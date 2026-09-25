<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Services\LegalContent;
use Illuminate\View\View;

/** Pages legales publiques (/mentions-legales, /cgu...). */
class PublicLegalPageController extends Controller
{
    public function __invoke(string $slug, LegalContent $content): View
    {
        $page = LegalPage::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('pages.legal', [
            'page' => $page,
            'html' => $content->render($page->content),
            'others' => LegalPage::query()->where('is_published', true)->whereKeyNot($page->id)->orderBy('position')->get(['title', 'slug']),
        ]);
    }
}
