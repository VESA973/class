<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin > Clients : fiches, recherche, doublons et fusion. */
class CustomerController extends Controller
{
    public function __construct(private readonly CustomerDirectory $directory)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        $customers = Customer::query()
            ->withCount(['reservations', 'quotes'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($search !== '', fn ($query) => $this->search($query, $search))
            ->orderByRaw('COALESCE(company_name, last_name, email) IS NULL')
            ->orderByRaw('LOWER(COALESCE(NULLIF(company_name, \'\'), last_name, email))')
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'duplicateCount' => $this->directory->duplicateCount(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.customers.edit', ['customer' => new Customer(['type' => 'particulier', 'source' => 'admin']), 'similar' => collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validated($request) + ['source' => 'admin']);

        return $this->savedRedirect($customer, 'Client créé.');
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['reservations.vehicle:id,name', 'quotes']);

        return view('admin.customers.edit', ['customer' => $customer, 'similar' => $this->directory->similarTo($customer)]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        return $this->savedRedirect($customer, 'Fiche client enregistrée.');
    }

    /** Archive la fiche (suppression douce) : les demandes et devis restent lisibles. */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('status', 'Client « '.$customer->display_name.' » supprimé de la base (demandes et devis conservés).');
    }

    public function duplicates(): View
    {
        return view('admin.customers.duplicates', ['groups' => $this->directory->duplicateGroups()]);
    }

    public function merge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'merge' => ['required', 'array', 'min:1'],
            'merge.*' => ['integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
        ], [
            'merge.required' => 'Cochez au moins une fiche à fusionner.',
        ]);

        $primary = Customer::findOrFail($data['primary']);
        $others = Customer::query()->whereIn('id', $data['merge'])->whereKeyNot($primary->id)->get();

        if ($others->isEmpty()) {
            return back()->withErrors(['merge' => 'Choisissez une fiche différente de la fiche conservée.']);
        }

        $this->directory->merge($primary, $others);

        return redirect()->route('admin.customers.duplicates')->with('status', $others->count().' fiche(s) fusionnée(s) dans « '.$primary->display_name.' ».');
    }

    /** Recherche pour l'editeur de devis (JSON). */
    public function lookup(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q'));

        if (mb_strlen($search) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = $this->search(Customer::query(), $search)->limit(8)->get()->map(fn (Customer $customer) => [
            'id' => $customer->id,
            'label' => $customer->display_name,
            'detail' => collect([$customer->email, $customer->phone])->filter()->implode(' · '),
            'name' => $customer->company_name ?: $customer->full_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->full_address,
        ]);

        return response()->json(['customers' => $customers]);
    }

    /** Chaque mot doit se retrouver dans un champ (« jean dupont », « dupont jean »…) ; un numero est compare sous toutes ses formes. */
    private function search($query, string $search)
    {
        $phone = preg_match('/^[+0-9 ().-]{6,}$/', $search) ? CustomerDirectory::phoneKey($search) : null;

        if ($phone) {
            $tail = '%'.substr($phone, -8).'%';

            return $query->where(fn ($inner) => $inner->where('phone_mobile_key', 'like', $tail)->orWhere('phone_landline_key', 'like', $tail));
        }

        foreach (preg_split('/\s+/', mb_strtolower($search)) as $word) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $word).'%';

            $query->where(fn ($inner) => $inner->whereRaw('LOWER(first_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(company_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(email) LIKE ?', [$like]));
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Customer::TYPES))],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100', 'required_without:company_name'],
            'company_name' => ['nullable', 'string', 'max:150', Rule::requiredIf($request->input('type') === 'professionnel')],
            'siret' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone_mobile' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9 ().-]{6,40}$/'],
            'phone_landline' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9 ().-]{6,40}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'last_name.required_without' => 'Indiquez le nom (ou la raison sociale).',
            'company_name.required_if' => 'Indiquez la raison sociale d’un client professionnel.',
            'company_name.required' => 'Indiquez la raison sociale d’un client professionnel.',
            'phone_mobile.regex' => 'Numéro de portable invalide.',
            'phone_landline.regex' => 'Numéro de fixe invalide.',
        ]);

        return $data;
    }

    /** Enregistre sans bloquer, mais signale les fiches ressemblantes. */
    private function savedRedirect(Customer $customer, string $message): RedirectResponse
    {
        $similar = $this->directory->similarTo($customer);
        $redirect = redirect()->route('admin.customers.edit', $customer)->with('status', $message);

        return $similar->isEmpty()
            ? $redirect
            : $redirect->with('duplicate_warning', $similar->count().' fiche(s) ressemblante(s) : '.$similar->map->display_name->implode(', ').'. Vérifiez dans « Doublons » si c’est la même personne.');
    }
}
