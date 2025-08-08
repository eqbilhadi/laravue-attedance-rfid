<?php

namespace App\Http\Controllers\AccessSettings;

use Throwable;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class RoleManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::query()->with('permissions:id,name');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('group')) {
            $group = $request->input('group');
            $query->when(!is_null($group), function ($query) use ($group) {
                $query->where('group', $group);
            });
        }

        $data = $query->paginate(10)->withQueryString();
        return Inertia::render('rbac/role/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'group']),
        ]);
    }

    public function create()
    {
        $permissions = Permission::orderBy('group')->get(['id', 'name', 'group'])->toArray();
        $menus = Menu::orderBy('sort_num', 'asc')
                    ->with('children', fn($query) => $query->whereIsActive(true))
                    ->whereNull('parent_id')
                    ->whereIsActive(true)
                    ->get(['id', 'label_name', 'sort_num'])
                    ->toArray();

        return Inertia::render('rbac/role/Form', [
            'permissions' => $permissions,
            'navigations' => $menus,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:sys_roles,name',
            'color' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:sys_permissions,id',
            'menus' => 'array',
            'menus.*' => 'exists:sys_menus,id', // jika ada relasi menus
        ]);

        try {
            $role = Role::create([
                'name' => $request->name,
                'color' => $request->color,
            ]);

            if ($request->filled('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            if ($request->filled('menus')) {
                $role->menus()->sync($request->menus);
            }
            foreach (User::pluck('id') as $userId) {
                Cache::forget("menus.{$userId}");
            }
            return redirect()->route('rbac.role.index')->with('success', 'Role created successfully.');
        } catch (Throwable $e) {
            Log::error('Role create failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to create role. Please try again.');
        }
    }

    public function edit(Role $sysRole)
    {
        $sysRole->load(['permissions:id', 'menus:id']);
        $permissions = Permission::orderBy('group')->get(['id', 'name', 'group'])->toArray();
        $menus = Menu::orderBy('sort_num', 'asc')
                    ->with('children', fn($query) => $query->whereIsActive(true))
                    ->whereNull('parent_id')
                    ->whereIsActive(true)
                    ->get(['id', 'label_name', 'sort_num'])
                    ->toArray();
        
        return Inertia::render('rbac/role/Form', [
            'permissions' => $permissions,
            'navigations' => $menus,
            'role' => [
                'id' => $sysRole->id,
                'name' => $sysRole->name,
                'color' => $sysRole->color,
                'permissions' => $sysRole->permissions()->pluck('id'),
                'menus' => $sysRole->menus()->pluck('id'),
            ]
        ]);
    }

    public function update(Request $request, Role $sysRole)
    {
        $request->validate([
            'name' => 'required|string|unique:sys_roles,name,' . $sysRole->id,
            'color' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:sys_permissions,id',
            'menus' => 'array',
            'menus.*' => 'exists:sys_menus,id',
        ]);

        try {
            $sysRole->update([
                'name' => $request->name,
                'color' => $request->color,
            ]);

            $sysRole->syncPermissions($request->permissions);
            $sysRole->menus()->sync($request->menus);
            foreach (User::pluck('id') as $userId) {
                Cache::forget("menus.{$userId}");
            }
            return redirect()->route('rbac.role.index')->with('success', 'Role created successfully.');
        } catch (Throwable $e) {
            Log::error('Role update failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to update role. Please try again.');
        }
    }

    public function destroy(Role $sysRole)
    {
        try {
            $sysRole->delete();
            return session()->flash('success', 'Role deleted successfully.');
        } catch (Throwable $e) {
            Log::error('Role delete failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to delete role. Please try again.');
        }
    }
}
