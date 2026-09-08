<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permission;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        User::query()->create($validated);

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

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, $user);
        $user->update($validated);

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

    private function validateUser(Request $request, ?User $user = null): array
    {
        $phoneRule = ['required', 'string', 'max:20'];
        $phoneRule[] = $user
            ? 'unique:users,phone,'.$user->id
            : 'unique:users,phone';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => $phoneRule,
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', 'in:'.implode(',', array_keys(Permission::roles()))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $validated['phone'] = PhoneNormalizer::toLocal($validated['phone']);

        if (! PhoneNormalizer::isValidIranMobile($validated['phone'])) {
            abort(422, 'شماره موبایل معتبر نیست.');
        }

        $validated['permissions'] = array_values($validated['permissions'] ?? []);
        $validated['password'] = $user?->password ?? Str::random(32);

        return $validated;
    }
}
