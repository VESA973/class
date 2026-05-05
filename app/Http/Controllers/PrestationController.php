<?php

namespace App\Http\Controllers;

use App\Models\Prestation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PrestationController extends Controller
{
    public function index(): View
    {
        return view('admin.prestations.index', [
            'prestations' => Prestation::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('admin.prestations.form', [
            'prestation' => new Prestation([
                'sort_order' => 0,
                'is_active' => true,
            ]),
            'action' => route('admin.prestations.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['image_path'] = $this->storeImage($request);

        Prestation::create($data);

        return redirect()
            ->route('admin.prestations.index')
            ->with('status', 'Prestation creee.');
    }

    public function edit(Prestation $prestation): View
    {
        return view('admin.prestations.form', [
            'prestation' => $prestation,
            'action' => route('admin.prestations.update', $prestation),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Prestation $prestation): RedirectResponse
    {
        $data = $this->validatedData($request);
        $imagePath = $this->storeImage($request);

        if ($imagePath) {
            if ($prestation->image_path) {
                Storage::disk('public')->delete($prestation->image_path);
            }

            $data['image_path'] = $imagePath;
        }

        $prestation->update($data);

        return redirect()
            ->route('admin.prestations.index')
            ->with('status', 'Prestation mise a jour.');
    }

    public function destroy(Prestation $prestation): RedirectResponse
    {
        if ($prestation->image_path) {
            Storage::disk('public')->delete($prestation->image_path);
        }

        $prestation->delete();

        return redirect()
            ->route('admin.prestations.index')
            ->with('status', 'Prestation supprimee.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        unset($data['image']);

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('prestations', 'public');
    }
}
