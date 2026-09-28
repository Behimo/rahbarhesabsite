<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnrollRequest;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(): View
    {
        $orders = Order::query()
            ->with(['user', 'items'])
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product', 'payment', 'coupon']);

        $licenses = SpotplayerLicense::query()
            ->with('course.product')
            ->where('order_id', $order->id)
            ->orWhere(function ($q) use ($order) {
                $q->where('user_id', $order->user_id);
            })
            ->get()
            ->unique('id');

        $enrollments = CourseEnrollment::query()
            ->with('course.product')
            ->where('user_id', $order->user_id)
            ->get();

        return view('admin.orders.show', compact('order', 'licenses', 'enrollments'));
    }

    public function markPaid(Order $order): RedirectResponse
    {
        if ($order->isPaid()) {
            return back()->with('success', 'این سفارش قبلاً پرداخت شده است.');
        }

        $this->orders->markPaid($order);

        return back()->with('success', 'سفارش پرداخت‌شده علامت خورد و دسترسی صادر شد.');
    }

    public function retryLicenses(Order $order): RedirectResponse
    {
        $this->orders->retryLicenses($order);

        return back()->with('success', 'صدور مجدد لایسنس در صف قرار گرفت.');
    }

    public function enroll(EnrollRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = User::query()->findOrFail($validated['user_id']);
        $course = Course::query()->findOrFail($validated['course_id']);

        $this->orders->enrollUser($user, $course, null, $validated['source']);

        return back()->with('success', 'دسترسی دوره برای کاربر ثبت شد.');
    }

    public function revokeEnrollment(CourseEnrollment $enrollment): RedirectResponse
    {
        $this->orders->revokeEnrollment($enrollment);

        return back()->with('success', 'دسترسی دوره لغو شد.');
    }
}
