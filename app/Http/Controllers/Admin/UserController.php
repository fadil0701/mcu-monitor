<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SqlFilter;
use App\Support\SqlLike;
use App\Support\UserPasswordRules;
use App\Support\UserRole;
use App\Support\ValidationMessages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $query = User::query()->orderBy('name');

        if (UserRole::isAdmin($actor) && ! UserRole::isSuperAdmin($actor)) {
            $query->where('role', UserRole::PESERTA);
        }

        if ($request->filled('search')) {
            $pattern = SqlLike::contains((string) $request->search);
            $query->where(function ($qry) use ($pattern) {
                $qry->where('name', 'like', $pattern)->orWhere('email', 'like', $pattern);
            });
        }

        $allowedRoles = UserRole::isSuperAdmin($actor)
            ? [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::PIMPINAN, UserRole::PESERTA]
            : [UserRole::PESERTA];

        $role = SqlFilter::enum(
            $request->filled('role') ? UserRole::normalize((string) $request->role) : null,
            $allowedRoles,
        );

        if ($role !== null) {
            $query->where('role', $role);
        }

        $users = $query->paginate(15)->withQueryString();
        $canAssignRoles = UserRole::canAssignRoles($actor);

        return view('admin.users.index', compact('users', 'canAssignRoles'));
    }

    public function create()
    {
        $canAssignRoles = UserRole::canAssignRoles(Auth::user());

        return view('admin.users.create', compact('canAssignRoles'));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', UserPasswordRules::defaults()],
            'role' => 'required|in:super_admin,admin,pimpinan,peserta',
            'is_active' => 'nullable|boolean',
        ], ValidationMessages::adminUser());

        $valid['role'] = UserRole::normalize($valid['role']);

        if (! UserRole::canCreateUserRole($actor, $valid['role'])) {
            abort(403, 'Anda tidak dapat membuat pengguna dengan role ini.');
        }

        $valid['password'] = Hash::make($valid['password']);
        $valid['is_active'] = (bool) ($valid['is_active'] ?? true);
        $user = User::create($valid);
        $user->syncRoles([$valid['role']]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        if (! UserRole::canEditUser(Auth::user(), $user)) {
            abort(403, 'Anda tidak dapat mengubah pengguna ini.');
        }

        $canAssignRoles = UserRole::canAssignRoles(Auth::user());

        return view('admin.users.edit', compact('user', 'canAssignRoles'));
    }

    public function update(Request $request, User $user)
    {
        $actor = Auth::user();

        if (! UserRole::canEditUser($actor, $user)) {
            abort(403, 'Anda tidak dapat mengubah pengguna ini.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', UserPasswordRules::defaults()],
            'is_active' => 'nullable|boolean',
        ];

        if (UserRole::canAssignRoles($actor)) {
            $rules['role'] = 'required|in:super_admin,admin,pimpinan,peserta';
        }

        $valid = $request->validate($rules, ValidationMessages::identity());

        if (! empty($valid['password'])) {
            $user->password = Hash::make($valid['password']);
        }

        $user->name = $valid['name'];
        $user->email = $valid['email'];
        $user->is_active = (bool) ($valid['is_active'] ?? true);

        if (UserRole::canAssignRoles($actor)) {
            $newRole = UserRole::normalize($valid['role']);

            if (! UserRole::canCreateUserRole($actor, $newRole)) {
                abort(403, 'Role tidak diizinkan.');
            }

            $user->role = $newRole;
        }

        $user->save();
        $user->syncRoles([$user->role]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diubah.');
    }

    public function destroy(User $user)
    {
        if (! UserRole::canDeleteUser(Auth::user(), $user)) {
            abort(403, 'Anda tidak dapat menghapus pengguna ini.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
