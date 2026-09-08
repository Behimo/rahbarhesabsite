<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private CartService $cart,
        private OtpService $otp,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('panel.dashboard');
        }

        return view('auth.login');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if (! PhoneNormalizer::isValidIranMobile($validated['phone'])) {
            return back()->withErrors(['phone' => 'شماره موبایل معتبر نیست.'])->withInput();
        }

        try {
            $this->otp->send($validated['phone']);
        } catch (\Throwable $e) {
            return back()->withErrors(['phone' => $e->getMessage()])->withInput();
        }

        session([
            'otp_phone' => PhoneNormalizer::toLocal($validated['phone']),
            'otp_name' => $validated['name'] ?? null,
        ]);

        return redirect()->route('login.verify')
            ->with('success', 'کد تأیید ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! session('otp_phone')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp', [
            'phone' => session('otp_phone'),
            'devCode' => $this->otp->peekLatestCode(session('otp_phone')),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $phone = session('otp_phone');

        if (! $phone) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:'.config('otp.length', 6)],
        ]);

        if (! $this->otp->verify($phone, $validated['code'])) {
            return back()->withErrors(['code' => 'کد تأیید نامعتبر یا منقضی شده است.']);
        }

        $user = User::findOrCreateByPhone($phone, session('otp_name'));

        Auth::login($user);
        $request->session()->regenerate();
        $this->cart->mergeGuestCart($user->id);

        session()->forget(['otp_phone', 'otp_name']);

        return redirect()->intended(route('panel.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
