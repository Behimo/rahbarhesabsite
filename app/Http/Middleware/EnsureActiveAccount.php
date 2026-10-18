<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'isBlocked') && $user->isBlocked()) {
            $message = method_exists($user, 'needsActivation') && $user->needsActivation()
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
