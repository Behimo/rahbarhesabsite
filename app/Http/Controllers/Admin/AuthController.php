<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Support\AccessCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user && AccessCatalog::allows($user, AccessCatalog::ACCESS_ADMIN)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $credentials['email'] = mb_strtolower(trim($credentials['email']));

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'ایمیل یا رمز عبور اشتباه است.'])->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->isBlocked() || ! AccessCatalog::allows($user, AccessCatalog::ACCESS_ADMIN)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'این حساب به پنل مدیریت دسترسی ندارد.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->markLoggedIn();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
