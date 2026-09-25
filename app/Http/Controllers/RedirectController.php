<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use App\Services\RedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Admin > SEO > Redirections (301 permanentes, 302 temporaires). */
class RedirectController extends Controller
{
    public function index(): View
    {
        return view('admin.seo.redirects', ['redirects' => Redirect::query()->latest('id')->paginate(30)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:500'],
            'to_url' => ['required', 'string', 'max:500'],
            'status_code' => ['required', Rule::in([301, 302])],
        ]);

        $from = RedirectService::normalize($data['from_path']);
        $to = str_starts_with($data['to_url'], 'http') ? $data['to_url'] : RedirectService::normalize($data['to_url']);

        if ($from === '/' || $from === '/admin' || str_starts_with($from, '/admin/')) {
            throw ValidationException::withMessages(['from_path' => 'L’accueil et l’administration ne peuvent pas être redirigés.']);
        }

        if ($from === $to || Redirect::query()->where('from_path', $to)->where('to_url', $from)->exists()) {
            throw ValidationException::withMessages(['to_url' => 'Cette redirection créerait une boucle.']);
        }

        Redirect::query()->updateOrCreate(['from_path' => $from], ['to_url' => $to, 'status_code' => (int) $data['status_code']]);

        return back()->with('status', "Redirection {$from} → {$to} enregistrée.");
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $redirect->delete();

        return back()->with('status', 'Redirection supprimée.');
    }
}
