<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Site\SiteController;
use App\Http\Requests\Panel\UpdatePasswordRequest;
use App\Http\Requests\Panel\UpdateProfileRequest;
use App\Models\CafeQuestion;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\SpotplayerLicense;
use App\Services\PaymentGatewayManager;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function orders(PaymentGatewayManager $payments): View
    {
        $orders = Order::query()
            ->with('items')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        $gateways = $payments->enabledOptions();

        return $this->render('panel.orders', compact('orders', 'gateways'));
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

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $phone = PhoneNormalizer::toLocal($validated['phone']);
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

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'is_wp_password' => false,
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return back()->with('success', 'رمز عبور به‌روزرسانی شد.');
    }

    public function questions(): View
    {
        $questions = CafeQuestion::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return $this->render('panel.questions', compact('questions'));
    }

    public function notifications(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->paginate(20);

        return $this->render('panel.notifications', compact('notifications'));
    }

    public function markNotificationRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('panel.notifications');

        return redirect()->to($url);
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'همه اعلان‌ها خوانده شدند.');
    }
}
