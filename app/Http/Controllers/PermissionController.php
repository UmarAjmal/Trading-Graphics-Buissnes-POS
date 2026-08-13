<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        
        $groupedPermissions = $permissions->groupBy('module');
        
        return Inertia::render('Permissions/Index', [
            'permissions' => $groupedPermissions->map(function ($modulePermissions, $module) {
                return [
                    'module' => $module ?: 'General',
                    'permissions' => $modulePermissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'slug' => $permission->slug,
                            'description' => $permission->description,
                            'is_active' => $permission->is_active,
                            'roles_count' => $permission->roles->count(),
                            'users_count' => $permission->users->count(),
                        ];
                    })
                ];
            })
        ]);
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        $modules = Permission::select('module')
            ->distinct()
            ->whereNotNull('module')
            ->pluck('module')
            ->toArray();
        
        return Inertia::render('Permissions/Create', [
            'modules' => $modules
        ]);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'description' => 'nullable|string|max:500',
            'module' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        Permission::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? '',
            'module' => $validated['module'] ?? 'general',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('permissions.index')
            ->with('message', 'Permission created successfully!');
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        $permission->load(['roles', 'users']);
        
        return Inertia::render('Permissions/Show', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'description' => $permission->description,
                'module' => $permission->module,
                'is_active' => $permission->is_active,
                'created_at' => $permission->created_at,
                'roles' => $permission->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                    ];
                }),
                'users' => $permission->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                })
            ]
        ]);
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        $modules = Permission::select('module')
            ->distinct()
            ->whereNotNull('module')
            ->pluck('module')
            ->toArray();
        
        return Inertia::render('Permissions/Edit', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'description' => $permission->description,
                'module' => $permission->module,
                'is_active' => $permission->is_active,
            ],
            'modules' => $modules
        ]);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $permission->id,
            'description' => 'nullable|string|max:500',
            'module' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $permission->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? '',
            'module' => $validated['module'] ?? 'general',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('permissions.index')
            ->with('message', 'Permission updated successfully!');
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        // Check if permission is assigned to any roles or users
        if ($permission->roles()->count() > 0 || $permission->users()->count() > 0) {
            return redirect()->route('permissions.index')
                ->with('error', 'Cannot delete permission that is assigned to roles or users.');
        }

        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('message', 'Permission deleted successfully!');
    }

    /**
     * Toggle permission status
     */
    public function toggleStatus(Permission $permission)
    {
        $permission->update(['is_active' => !$permission->is_active]);
        
        $status = $permission->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('permissions.index')
            ->with('message', "Permission {$status} successfully!");
    }
}