<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Site\SiteController;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends SiteController
{
    public function index(): View
    {
        $user = auth()->user();

        $enrollments = CourseEnrollment::query()
            ->with(['course.product'])
            ->where('user_id', $user->id)
            ->latest('enrolled_at')
            ->take(5)
            ->get();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return $this->render('panel.dashboard', compact('enrollments', 'orders'));
    }

    public function courses(): View
    {
        $enrollments = CourseEnrollment::query()
            ->with(['course.product', 'course.sections.lessons'])
            ->where('user_id', auth()->id())
            ->latest('enrolled_at')
            ->get();

        $licenses = SpotplayerLicense::query()
            ->where('user_id', auth()->id())
            ->get()
            ->keyBy('course_id');

        return $this->render('panel.courses', compact('enrollments', 'licenses'));
    }

    public function orders(): View
    {
        $orders = Order::query()
            ->with('items')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return $this->render('panel.orders', compact('orders'));
    }

    public function profile(): View
    {
        $user = auth()->user();

        return $this->render('panel.profile', [
            'user' => $user,
            'enrollmentsCount' => CourseEnrollment::query()->where('user_id', $user->id)->count(),
            'ordersCount' => Order::query()->where('user_id', $user->id)->count(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20'],
        ], [
            'first_name.required' => 'نام را وارد کنید.',
            'email.email' => 'ایمیل را درست وارد کنید.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'phone.required' => 'شماره موبایل را وارد کنید.',
        ]);

        $phone = PhoneNormalizer::toLocal($validated['phone']);

        if (! PhoneNormalizer::isValidIranMobile($phone)) {
            throw ValidationException::withMessages([
                'phone' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
            ]);
        }

        $phoneTaken = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($query) use ($phone) {
                $query->where('phone', $phone)->orWhere('mobile', $phone);
            })
            ->exists();

        if ($phoneTaken) {
            throw ValidationException::withMessages([
                'phone' => 'این شماره موبایل قبلاً ثبت شده است.',
            ]);
        }

        $email = filled($validated['email']) ? $validated['email'] : null;
        $phoneChanged = $phone !== ($user->mobile ?: $user->phone);
        $emailChanged = $email !== $user->email;

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?: null,
            'name' => trim($validated['first_name'].' '.($validated['last_name'] ?? '')),
            'email' => $email,
            'phone' => $phone,
            'mobile' => $phone,
            'mobile_verified_at' => $phoneChanged ? null : $user->mobile_verified_at,
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ]);

        return back()->with('success', 'مشخصات پرونده ذخیره شد.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'رمز جدید را وارد کنید.',
            'password.min' => 'رمز جدید باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز با رمز جدید یکی نیست.',
        ]);

        $request->user()->forceFill([
            'password' => $validated['password'],
            'is_wp_password' => false,
        ])->save();

        return back()->with('success', 'رمز عبور به‌روزرسانی شد.');
    }
}
