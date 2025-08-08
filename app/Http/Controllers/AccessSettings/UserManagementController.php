<?php

namespace App\Http\Controllers\AccessSettings;

use Throwable;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        /** @var \Illuminate\Database\Eloquent\Builder<User> $query */
        $query = User::query()->with('roles')->whereNot('id', Auth::id())->latest();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            $query->when(!is_null($status), function ($query) use ($status) {
                $query->where('is_active', $status);
            });
        }

        $data = $query->paginate(10)->withQueryString();

        return Inertia::render('rbac/user/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'status', 'gender']),
        ]);
    }

    public function create()
    {
        $roles = \App\Models\Role::get(['id', 'name'])
            ->map(fn($role) => [
                'label' => $role->name,
                'value' => $role->id,
            ])->toArray();

        return Inertia::render('rbac/user/Form', [
            "roles" => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "name" => "required|max:255",
            "email" => "required|email|unique:sys_users,email",
            "username" => "required|lowercase|unique:sys_users,username|max:255|min:3",
            "password" => "required|min:8|max:255",
            "is_active" => "required|boolean",
            "birthplace" => "required|max:255",
            "birthdate" => "required|date",
            "gender" => "required|in:l,p",
            "phone" => "required|numeric|digits_between:10,15",
            "address" => "required|max:255",
            "role" => "required|exists:sys_roles,id",
        ]);

        try {
            $user = User::create($validated);
            $user->assignRole($request->input('role'));

            return redirect()->route('rbac.user.index')->with('success', 'User created successfully.');
        } catch (Throwable $e) {
            Log::error('User create failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to create user. Please try again.');
        }
    }

    public function edit(User $sysUser)
    {
        $sysUser->role = $sysUser->roles->pluck('id')->first() ?? null;

        $roles = \App\Models\Role::get(['id', 'name'])
            ->map(fn($role) => [
                'label' => $role->name,
                'value' => $role->id,
            ])->toArray();

        return Inertia::render('rbac/user/Form', [
            'user' => $sysUser,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $sysUser)
    {
        $validated = $request->validate([
            "name" => "required|max:255",
            "email" => "required|email|unique:sys_users,email," . $sysUser->id,
            "username" => "required|lowercase|unique:sys_users,username," . $sysUser->id . "|max:255|min:3",
            "password" => "nullable|min:8|max:255",
            "is_active" => "required|boolean",
            "birthplace" => "required|max:255",
            "birthdate" => "required|date",
            "gender" => "required|in:l,p",
            "phone" => "required|numeric|digits_between:10,15",
            "address" => "required|max:255",
            "role" => "required|exists:sys_roles,id",
        ]);

        try {
            // Jika password tidak diisi, jangan update password
            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            $sysUser->update($validated);
            $sysUser->syncRoles([$request->input('role')]);
            Cache::forget("menus.{$sysUser->id}");

            return redirect()->route('rbac.user.index')->with('success', 'User updated successfully.');
        } catch (Throwable $e) {
            Log::error('User update failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to update user. Please try again.');
        }
    }

    public function destroy(User  $sysUser)
    {
        try {
            Cache::forget("menus.{$sysUser->id}");
            $sysUser->delete();

            return redirect()->route('rbac.user.index')->with('success', 'User deleted successfully.');
        } catch (Throwable $e) {
            Log::error('User delete failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Failed to delete user. Please try again.');
        }
    }

    public function search(Request $request)
    {
        $search = $request->query('search');
        
        $users = User::query()
            ->when($search, fn($q) =>
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
            )
            ->limit(10)
            ->get(['id', 'name']);


        return response()->json($users);
    }
}
