<?php

namespace App\Http\Controllers\AccessSettings;

use Throwable;
use Inertia\Inertia;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PermissionManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();

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
        return Inertia::render('rbac/permission/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'group']),
            'groups' => Permission::select('group')
                ->distinct()
                ->pluck('group')
                ->map(fn($group) => [
                    'label' => $group,
                    'value' => $group,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'group' => 'required|string|max:255',
            ]);
    
            Permission::create($data);
    
            return session()->flash('success', 'Permission created successfully.');
        } catch (Throwable $e) {
            Log::error('Permission create failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to create permission. Please try again.');
        }
    }

    public function update(Request $request, Permission $sysPermission)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'group' => 'required|string|max:255',
            ]);
    
            $sysPermission->update($data);
    
            return session()->flash('success', 'Permission updated successfully.');
        } catch (Throwable $e) {
            Log::error('Permission update failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to update permission. Please try again.');
        }
    }

    public function destroy(Permission $sysPermission)
    {
        try {
            $sysPermission->delete();
            return session()->flash('success', 'Permission deleted successfully.');
        } catch (Throwable $e) {
            Log::error('Permission delete failed', [
                'error' => $e->getMessage(),
            ]);

            return session()->flash('error', 'Failed to delete permission. Please try again.');
        }
    }
}
