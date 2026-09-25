<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Models\LegalPageVersion;
use App\Services\LegalContent;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin > Pages legales : edition (texte riche), versions, informations legales de l'entreprise. */
class LegalPageController extends Controller
{
    public function __construct(private readonly LegalContent $content)
    {
    }

    public function index(): View
    {
        return view('admin.legal.index', [
            'pages' => LegalPage::query()->withCount('versions')->orderBy('position')->get(),
            'missing' => $this->content->missing(),
        ]);
    }

    public function edit(LegalPage $legalPage): View
    {
        return view('admin.legal.edit', [
            'page' => $legalPage,
            'versions' => $legalPage->versions()->with('user:id,name')->limit(30)->get(),
            'variables' => collect(LegalContent::VARIABLES)->map(fn ($item) => $item[0])->put('site_url', 'Adresse du site'),
            'missing' => $this->content->missing(),
        ]);
    }

    public function update(Request $request, LegalPage $legalPage): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:200000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['content.required' => 'La page ne peut pas être vide.']);

        $content = $this->content->sanitize($data['content']);
        $changed = $content !== $legalPage->content || $data['title'] !== $legalPage->title;

        $legalPage->update([
            'title' => $data['title'],
            'content' => $content,
            'is_published' => $request->boolean('is_published'),
        ]);

        if ($changed) {
            $legalPage->versions()->create([
                'user_id' => $request->user()?->id,
                'title' => $data['title'],
                'content' => $content,
                'note' => $data['note'] ?? null,
            ]);
        }

        return redirect()->route('admin.legal.edit', $legalPage)->with('status', $changed ? 'Page enregistrée (nouvelle version créée).' : 'Aucune modification du contenu.');
    }

    public function version(LegalPage $legalPage, LegalPageVersion $version): View
    {
        abort_unless($version->legal_page_id === $legalPage->id, 404);

        return view('admin.legal.version', ['page' => $legalPage, 'version' => $version, 'html' => $this->content->render($version->content)]);
    }

    public function restore(Request $request, LegalPage $legalPage, LegalPageVersion $version): RedirectResponse
    {
        abort_unless($version->legal_page_id === $legalPage->id, 404);

        $legalPage->update(['title' => $version->title, 'content' => $version->content]);
        $legalPage->versions()->create([
            'user_id' => $request->user()?->id,
            'title' => $version->title,
            'content' => $version->content,
            'note' => 'Restauration de la version du '.$version->created_at->timezone(config('app.local_timezone'))->format('d/m/Y H:i'),
        ]);

        return redirect()->route('admin.legal.edit', $legalPage)->with('status', 'Version restaurée.');
    }

    public function info(Settings $settings): View
    {
        return view('admin.legal.info', [
            'values' => $this->content->values(),
            'variables' => LegalContent::VARIABLES,
        ]);
    }

    public function updateInfo(Request $request, Settings $settings): RedirectResponse
    {
        $rules = [];
        foreach (array_keys(LegalContent::VARIABLES) as $variable) {
            $rules[$variable] = ['nullable', 'string', 'max:500'];
        }
        $rules['email'][] = 'email';
        $rules['mediateur_site'][] = 'url';
        $data = $request->validate($rules, ['mediateur_site.url' => 'Le site du médiateur doit être une adresse complète (https://…).']);

        // Champs partages avec les devis (Devis > Reglages) et champs propres aux pages legales.
        $company = (array) $settings->get('quotes.company', []);
        $legal = (array) $settings->get('legal.info', []);

        foreach (LegalContent::VARIABLES as $variable => [, $source]) {
            [$group, $key] = explode('.', $source, 2);
            if ($group === 'company') {
                $company[$key] = $data[$variable] ?? '';
            } else {
                $legal[$key] = $data[$variable] ?? '';
            }
        }

        $settings->set(['quotes.company' => $company, 'legal.info' => $legal]);

        return redirect()->route('admin.legal.info')->with('status', 'Informations légales enregistrées. Elles sont utilisées dans les pages légales et sur les devis.');
    }
}
