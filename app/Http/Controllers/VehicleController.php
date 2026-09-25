<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use Illuminate\Http\JsonResponse;
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
            // Liste de secours (sans JavaScript) ; le gestionnaire charge ses donnees via data().
            'vehicles' => Vehicle::query()->latest()->paginate(12),
            'props' => [
                'csrfToken' => csrf_token(),
                'initialVehicleId' => request()->integer('vehicle') ?: null,
                'urls' => [
                    'data' => route('admin.vehicles.data'),
                    'store' => route('admin.vehicles.store'),
                    'item' => route('admin.vehicles.update', ['vehicle' => '__ID__']),
                    'planning' => route('admin.planning.index'),
                ],
            ],
        ]);
    }

    /** Liste complete pour le gestionnaire en deux colonnes (recherche, filtres et tri cote navigateur). */
    public function data(): JsonResponse
    {
        $now = ReservationAvailability::now();
        $blocking = ReservationAvailability::BLOCKING_STATUSES;

        // withExists / withCount : une seule requete, pas de N+1.
        $vehicles = Vehicle::query()
            ->withExists(['reservations as reserved_now' => fn ($query) => $query
                ->whereIn('status', $blocking)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>', $now)])
            ->withCount(['reservations as upcoming_count' => fn ($query) => $query
                ->whereIn('status', $blocking)
                ->where('start_at', '>', $now)])
            ->orderBy('name')
            ->limit(500)
            ->get();

        return response()->json([
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => $this->payload($vehicle))->values(),
            'categories' => $vehicles->pluck('category')->unique()->sort()->values(),
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['image_path'] = $this->storeImage($request);
        $data['model_path'] = $this->storeModel($request);

        $vehicle = Vehicle::create($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Véhicule créé.', 'vehicle' => $this->payload($vehicle->fresh())], 201);
        }

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

    public function update(Request $request, Vehicle $vehicle): RedirectResponse|JsonResponse
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

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Véhicule mis à jour.', 'vehicle' => $this->payload($vehicle->fresh())]);
        }

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', 'Vehicule mis a jour.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse|JsonResponse
    {
        if ($vehicle->image_path) {
            Storage::disk('public')->delete($vehicle->image_path);
        }

        if ($vehicle->model_path) {
            Storage::disk('public')->delete($vehicle->model_path);
        }

        $vehicle->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Véhicule supprimé.']);
        }

        return redirect()
            ->route('admin.vehicles.index')
            ->with('status', 'Vehicule supprime.');
    }

    /** @return array<string, mixed> */
    private function payload(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'slug' => $vehicle->slug,
            'category' => $vehicle->category,
            'horsepower' => $vehicle->horsepower,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'seats' => $vehicle->seats,
            'daily_price' => $vehicle->daily_price,
            'image_url' => $vehicle->image_url,
            'has_uploaded_image' => (bool) $vehicle->image_path,
            'display_image' => $vehicle->display_image,
            'model_url' => $vehicle->model_url,
            'video_url' => $vehicle->video_url,
            'description' => $vehicle->description,
            'is_available' => $vehicle->is_available,
            'with_chauffeur' => $vehicle->with_chauffeur,
            'reserved_now' => (bool) ($vehicle->reserved_now ?? false),
            'upcoming_count' => (int) ($vehicle->upcoming_count ?? 0),
            'public_url' => $vehicle->is_available && $vehicle->slug ? route('vehicles.show', $vehicle) : null,
            'updated_at' => $vehicle->updated_at?->toIso8601String(),
        ];
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
