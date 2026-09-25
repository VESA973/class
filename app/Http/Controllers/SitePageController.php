<?php

namespace App\Http\Controllers;

use App\Models\Prestation;
use Illuminate\View\View;

/** Pages Prestations et Contact au nouveau design (les anciennes restent dans HomeController). */
class SitePageController extends Controller
{
    public function prestations(): View
    {
        return view('pages.prestations-v2', [
            'prestations' => Prestation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact-v2');
    }
}
