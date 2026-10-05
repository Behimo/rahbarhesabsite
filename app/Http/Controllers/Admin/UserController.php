<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->with('roles')->latest()->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User,
            'roles' => AccessCatalog::assignableRoles(),
            'currentRole' => AccessCatalog::ROLE_USER,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::query()->create($request->userAttributes());
        $this->syncAccess($request, $user);

        return redirect()->route('admin.users.index')->with('success', 'کاربر ایجاد شد.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('admin.users.form', [
            'user' => $user,
            'roles' => AccessCatalog::assignableRoles(),
            'currentRole' => $user->roles->first()?->name ?? AccessCatalog::ROLE_USER,
            'ordersCount' => $user->orders()->count(),
            'enrollmentsCount' => $user->enrollments()->count(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $role = (string) $request->validated('role');

        if ($request->user()?->is($user) && $request->validated('status') !== 'active') {
            return back()->withErrors(['status' => 'نمی‌توانید حساب خودتان را مسدود کنید.'])->withInput();
        }

        if ($request->user()?->is($user) && $role !== AccessCatalog::ROLE_ADMIN) {
            return back()->withErrors(['role' => 'نمی‌توانید نقش خودتان را تغییر دهید.'])->withInput();
        }

        if ($user->hasRole(AccessCatalog::ROLE_ADMIN) && $role !== AccessCatalog::ROLE_ADMIN) {
            $adminCount = User::query()->role(AccessCatalog::ROLE_ADMIN)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'حداقل یک مدیر سیستم باید باقی بماند.'])->withInput();
            }
        }

        $user->update($request->userAttributes());
        $this->syncAccess($request, $user);

        return redirect()->route('admin.users.index')->with('success', 'کاربر به‌روزرسانی شد.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'نمی‌توانید خودتان را حذف کنید.']);
        }

        if ($user->hasRole(AccessCatalog::ROLE_ADMIN) && User::query()->role(AccessCatalog::ROLE_ADMIN)->count() <= 1) {
            return back()->withErrors(['user' => 'حداقل یک مدیر سیستم باید باقی بماند.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'کاربر حذف شد.');
    }

    private function syncAccess(UserRequest $request, User $user): void
    {
        $user->syncRoles([(string) $request->validated('role')]);
        $user->syncPermissions([]);
    }
}
