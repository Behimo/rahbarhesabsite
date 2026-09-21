<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\Order;
use Illuminate\View\View;

class DashboardController extends Controller
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

        return view('panel.dashboard', compact('enrollments', 'orders'));
    }

    public function courses(): View
    {
        $enrollments = CourseEnrollment::query()
            ->with(['course.product', 'course.sections.lessons'])
            ->where('user_id', auth()->id())
            ->latest('enrolled_at')
            ->get();

        $licenses = \App\Models\SpotplayerLicense::query()
            ->where('user_id', auth()->id())
            ->get()
            ->keyBy('course_id');

        return view('panel.courses', compact('enrollments', 'licenses'));
    }

    public function orders(): View
    {
        $orders = Order::query()
            ->with('items')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('panel.orders', compact('orders'));
    }

    public function profile(): View
    {
        return view('panel.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        if (! empty($validated['phone'])) {
            $local = \App\Support\PhoneNormalizer::toLocal($validated['phone']);
            $validated['phone'] = $local;
            $validated['mobile'] = $local;
        }

        auth()->user()->update($validated);

        return back()->with('success', 'پروفایل به‌روزرسانی شد.');
    }
}
