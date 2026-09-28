<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Support\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => User::ROLE_USER]),
            'roles' => Permission::roles(),
            'permissions' => Permission::labels(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::query()->create($request->userAttributes());

        return redirect()->route('admin.users.index')->with('success', 'کاربر ایجاد شد.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => Permission::roles(),
            'permissions' => Permission::labels(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->userAttributes());

        return redirect()->route('admin.users.index')->with('success', 'کاربر به‌روزرسانی شد.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'نمی‌توانید خودتان را حذف کنید.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'کاربر حذف شد.');
    }
}
