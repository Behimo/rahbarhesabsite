<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'لطفاً وارد حساب کاربری شوید.');
        }

        if ($user->isBlocked()) {
            $message = $user->needsActivation()
                ? 'حساب کاربری شما فعال نیست. لطفاً با ایمیل/رمز وارد شوید و شماره موبایل را تأیید کنید.'
                : 'حساب کاربری شما غیرفعال است.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, $message);
        }

        return $next($request);
    }
}
