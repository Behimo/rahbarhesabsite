<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignOrderUserRequest;
use App\Http\Requests\Admin\EnrollRequest;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $filter = (string) $request->query('filter', '');

        $orders = Order::query()
            ->with(['user', 'items'])
            ->when($filter === 'needs_review', fn ($q) => $q->where('needs_review', true))
            ->when($filter === 'without_user', fn ($q) => $q->whereNull('user_id'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'filter'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product', 'payments', 'coupon']);

        $licenses = SpotplayerLicense::query()
            ->with('course.product')
            ->where(function ($q) use ($order) {
                $q->where('order_id', $order->id);
                if ($order->user_id) {
                    $q->orWhere('user_id', $order->user_id);
                }
            })
            ->get()
            ->unique('id');

        $enrollments = $order->user_id
            ? CourseEnrollment::query()
                ->with('course.product')
                ->where('user_id', $order->user_id)
                ->get()
            : collect();

        $assignableUsers = $order->needsReview()
            ? User::query()->orderByDesc('id')->limit(200)->get(['id', 'name', 'mobile', 'phone', 'email'])
            : collect();

        return view('admin.orders.show', compact('order', 'licenses', 'enrollments', 'assignableUsers'));
    }

    public function assignUser(AssignOrderUserRequest $request, Order $order): RedirectResponse
    {
        $user = User::query()->findOrFail($request->validated('user_id'));

        $order->forceFill([
            'user_id' => $user->id,
            'needs_review' => false,
        ])->save();

        if ($order->isPaid()) {
            $order->loadMissing('items.product');
            foreach ($order->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }
                foreach ($product->relatedCourses() as $course) {
                    $this->orders->enrollUser($user, $course, $order, CourseEnrollment::SOURCE_PURCHASE);
                }
            }
        }

        return back()->with('success', 'کاربر به سفارش اختصاص یافت و در صورت نیاز دسترسی صادر شد.');
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
