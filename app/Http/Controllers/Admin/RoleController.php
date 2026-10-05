<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use App\Support\AccessCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->where('guard_name', AccessCatalog::guard())
            ->withCount(['permissions', 'users'])
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'role' => new Role,
            'permissions' => AccessCatalog::labels(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::query()->create([
            'name' => $request->validated('name'),
            'label' => $request->validated('label'),
            'guard_name' => AccessCatalog::guard(),
            'is_system' => false,
        ]);

        $role->syncPermissions(AccessCatalog::normalize($request->validated('permissions') ?? []));

        return redirect()->route('admin.roles.index')->with('success', 'نقش ایجاد شد.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('admin.roles.form', [
            'role' => $role,
            'permissions' => AccessCatalog::labels(),
            'selectedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $role->label = $request->validated('label');

        if (! $role->is_system) {
            $role->name = $request->validated('name');
        }

        $role->save();
        $role->syncPermissions(AccessCatalog::normalize($request->validated('permissions') ?? []));

        return redirect()->route('admin.roles.index')->with('success', 'نقش به‌روزرسانی شد.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'نقش‌های سیستمی حذف نمی‌شوند.']);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['role' => 'این نقش به کاربر وصل است. اول نقش آن کاربران را عوض کنید.']);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'نقش حذف شد.');
    }
}
