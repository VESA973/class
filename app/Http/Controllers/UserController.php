<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['is_active' => true]),
            'action' => route('admin.users.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validatedData($request));

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Utilisateur cree.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatedData($request, $user);

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'Vous ne pouvez pas desactiver votre propre compte.']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Utilisateur mis a jour.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Utilisateur supprime.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        // En modification, un mot de passe vide conserve l'actuel.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
