<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at']);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => ['required', Rule::in(['admin', 'trainer', 'trainee'])],
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'data' => $user->makeHidden(['password'])], 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user->makeHidden(['password'])->only(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:6',
            'role' => ['sometimes', Rule::in(['admin', 'trainer', 'trainee'])],
            'is_active' => 'sometimes|boolean',
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->fill(collect($data)->except('password')->toArray());
        $user->save();

        return response()->json(['success' => true, 'data' => $user->fresh()->makeHidden(['password'])]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'error' => 'Vous ne pouvez pas supprimer votre propre compte'], 400);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'Utilisateur supprimé']);
    }
}
