<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Site\SiteController;
use App\Http\Requests\Auth\PasswordLoginRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends SiteController
{
    public function __construct(
        SiteDataService $siteData,
        SeoService $seo,
        private CartService $cart,
        private OtpService $otp,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        $intended = $this->safeIntendedUrl($request->query('redirect'));

        if ($intended) {
            $request->session()->put('url.intended', $intended);
        }

        if (Auth::check()) {
            return redirect()->intended(route('panel.dashboard'));
        }

        return $this->render('auth.login');
    }

    public function sendOtp(SendOtpRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $existing = User::findByPhone($validated['phone']);
        if ($existing?->needsActivation()) {
            return back()->withErrors([
                'phone' => 'این حساب نیاز به فعال‌سازی دارد. با ایمیل و رمز عبور وارد شوید و موبایل را تأیید کنید.',
            ])->withInput();
        }
        if ($existing?->isBlocked()) {
            return back()->withErrors(['phone' => 'حساب کاربری شما غیرفعال است.'])->withInput();
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
        session()->forget('activate_user_id');

        return redirect()->route('login.verify')
            ->with('success', 'کد تأیید ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! session('otp_phone')) {
            return redirect()->route('login');
        }

        return $this->render('auth.verify-otp', [
            'phone' => session('otp_phone'),
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $phone = session('otp_phone');

        if (! $phone) {
            return redirect()->route('login');
        }

        if (! $this->otp->verify($phone, $request->validated('code'))) {
            return back()->withErrors(['code' => 'کد تأیید نامعتبر یا منقضی شده است.']);
        }

        $local = PhoneNormalizer::toLocal($phone);
        $activateUserId = session('activate_user_id');

        if ($activateUserId) {
            $user = User::query()->find($activateUserId);
            if (! $user || ! $user->needsActivation()) {
                session()->forget(['otp_phone', 'otp_name', 'activate_user_id']);

                return redirect()->route('login')->withErrors(['phone' => 'نشست فعال‌سازی نامعتبر است. دوباره وارد شوید.']);
            }

            $taken = User::query()
                ->where('id', '!=', $user->id)
                ->where(function ($q) use ($local) {
                    $q->where('mobile', $local)->orWhere('phone', $local);
                })
                ->exists();

            if ($taken) {
                return back()->withErrors(['code' => 'این شماره موبایل متعلق به حساب دیگری است.']);
            }

            $user->forceFill([
                'mobile' => $local,
                'phone' => $local,
                'claimed_mobile' => null,
                'status' => User::STATUS_ACTIVE,
                'mobile_verified_at' => now(),
                'last_login_at' => now(),
            ])->save();
        } else {
            $user = User::findOrCreateByPhone($phone, session('otp_name'));

            if ($user->needsActivation()) {
                return redirect()->route('login')->withErrors([
                    'phone' => 'این حساب نیاز به فعال‌سازی دارد. با ایمیل و رمز عبور وارد شوید.',
                ]);
            }

            if ($user->isBlocked()) {
                return redirect()->route('login')->withErrors(['phone' => 'حساب کاربری شما غیرفعال است.']);
            }

            $user->forceFill([
                'mobile' => $local,
                'phone' => $local,
                'mobile_verified_at' => now(),
                'last_login_at' => now(),
            ])->save();
        }

        $guestSessionId = $request->session()->getId();
        Auth::login($user);
        $request->session()->regenerate();
        $this->cart->mergeGuestCart($user->id, $guestSessionId);

        session()->forget(['otp_phone', 'otp_name', 'activate_user_id']);

        return redirect()->intended(route('panel.dashboard'));
    }

    public function loginPassword(PasswordLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $login = $validated['login'];
        $phone = PhoneNormalizer::toLocal($login);
        $user = User::query()
            ->where(function ($query) use ($login, $phone) {
                $query->where('email', $login);

                if ($phone !== '') {
                    $query->orWhere('phone', $phone)->orWhere('mobile', $phone);
                }
            })
            ->first();

        if (! $user || ! $user->passwordMatches($validated['password'])) {
            return back()->withErrors(['login' => 'اطلاعات ورود نادرست است.'])->withInput();
        }

        if ($user->needsActivation()) {
            if ($user->is_wp_password) {
                $user->forceFill([
                    'password' => $validated['password'],
                    'is_wp_password' => false,
                ])->save();
            }

            session(['activate_user_id' => $user->id]);

            return redirect()->route('login.activate')
                ->with('success', 'برای فعال‌سازی حساب، شماره موبایل را وارد و کد تأیید را دریافت کنید.');
        }

        if ($user->isBlocked()) {
            return back()->withErrors(['login' => 'حساب کاربری شما غیرفعال است.']);
        }

        if ($user->is_wp_password) {
            $user->forceFill([
                'password' => $validated['password'],
                'is_wp_password' => false,
            ])->save();
        }

        $user->markLoggedIn();
        $guestSessionId = $request->session()->getId();
        Auth::login($user);
        $request->session()->regenerate();
        $this->cart->mergeGuestCart($user->id, $guestSessionId);

        return redirect()->intended(route('panel.dashboard'));
    }

    public function showActivate(Request $request): View|RedirectResponse
    {
        $user = $this->activationUser();
        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['login' => 'برای فعال‌سازی ابتدا با ایمیل و رمز عبور وارد شوید.']);
        }

        return $this->render('auth.activate', [
            'user' => $user,
            'suggestedMobile' => $user->claimed_mobile,
        ]);
    }

    public function sendActivateOtp(Request $request): RedirectResponse
    {
        $user = $this->activationUser();
        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['login' => 'برای فعال‌سازی ابتدا با ایمیل و رمز عبور وارد شوید.']);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ], [], ['phone' => 'شماره موبایل']);

        $local = PhoneNormalizer::toLocal($validated['phone']);
        if (! PhoneNormalizer::isValidIranMobile($local)) {
            return back()->withErrors(['phone' => 'شماره موبایل معتبر نیست.'])->withInput();
        }

        $taken = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($local) {
                $q->where('mobile', $local)->orWhere('phone', $local);
            })
            ->exists();

        if ($taken) {
            return back()->withErrors(['phone' => 'این شماره موبایل قبلاً ثبت شده است.'])->withInput();
        }

        try {
            $this->otp->send($local);
        } catch (\Throwable $e) {
            return back()->withErrors(['phone' => $e->getMessage()])->withInput();
        }

        session([
            'otp_phone' => $local,
            'activate_user_id' => $user->id,
        ]);
        session()->forget('otp_name');

        return redirect()->route('login.verify')
            ->with('success', 'کد تأیید ارسال شد. پس از تأیید، حساب شما فعال می‌شود.');
    }

    private function activationUser(): ?User
    {
        $id = session('activate_user_id');
        if (! $id) {
            return null;
        }

        $user = User::query()->find($id);

        return $user && $user->needsActivation() ? $user : null;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function safeIntendedUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;
        if ($host !== null && $host !== request()->getHost()) {
            return null;
        }

        $path = $parts['path'] ?? null;
        if (! is_string($path) || ! str_starts_with($path, '/')) {
            return null;
        }

        return isset($parts['query']) ? $path.'?'.$parts['query'] : $path;
    }
}
