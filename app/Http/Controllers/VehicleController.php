<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        return view('admin.vehicles.index', [
            'vehicles' => Vehicle::query()->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('admin.vehicles.form', [
            'vehicle' => new Vehicle([
                'fuel_type' => 'Essence',
                'transmission' => 'Auto',
                'is_available' => true,
            ]),
            'action' => route('admin.vehicles.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['image_path'] = $this->storeImage($request);
        $data['model_path'] = $this->storeModel($request);

        Vehicle::create($data);

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', 'Vehicule cree.');
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.form', [
            'vehicle' => $vehicle,
            'action' => route('admin.vehicles.update', $vehicle),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $this->validatedData($request);
        $imagePath = $this->storeImage($request);

        if ($imagePath) {
            if ($vehicle->image_path) {
                Storage::disk('public')->delete($vehicle->image_path);
            }

            $data['image_path'] = $imagePath;
        }

        $modelPath = $this->storeModel($request);

        if ($modelPath || $request->boolean('remove_model')) {
            if ($vehicle->model_path) {
                Storage::disk('public')->delete($vehicle->model_path);
            }

            $data['model_path'] = $modelPath;
        }

        $vehicle->update($data);

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', 'Vehicule mis a jour.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->image_path) {
            Storage::disk('public')->delete($vehicle->image_path);
        }

        if ($vehicle->model_path) {
            Storage::disk('public')->delete($vehicle->model_path);
        }

        $vehicle->delete();

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', 'Vehicule supprime.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'category' => ['required', 'string', 'max:80'],
            'horsepower' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'fuel_type' => ['required', 'string', 'max:60'],
            'transmission' => ['required', 'string', 'max:60'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:9'],
            'daily_price' => ['required', 'integer', 'min:1', 'max:100000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'model' => ['nullable', 'file', 'extensions:glb', 'max:51200'],
            'video_url' => ['nullable', 'url', 'max:500'],
        ], [
            'model.extensions' => 'Le modèle 3D doit être un fichier .glb.',
            'model.max' => 'Le modèle 3D ne doit pas dépasser 50 Mo.',
            'model.uploaded' => 'Le modèle 3D est trop lourd pour le serveur (limite PHP upload_max_filesize).',
        ]);

        $data['is_available'] = $request->boolean('is_available');
        $data['with_chauffeur'] = $request->boolean('with_chauffeur');

        unset($data['image'], $data['model']);

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('vehicles', 'public');
    }

    private function storeModel(Request $request): ?string
    {
        if (! $request->hasFile('model')) {
            return null;
        }

        // Extension forcee : un .glb est souvent detecte comme "bin".
        return $request->file('model')->storeAs('vehicles/models', Str::random(40).'.glb', 'public');
    }
}
