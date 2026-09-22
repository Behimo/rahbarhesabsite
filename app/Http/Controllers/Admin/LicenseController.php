<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SpotplayerLicense;
use App\Services\SpotPlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function __construct(private SpotPlayerService $spotPlayer) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $courseId = $request->query('course_id');
        $q = trim((string) $request->query('q', ''));

        $licenses = SpotplayerLicense::query()
            ->with(['user', 'course.product', 'order'])
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->when(filled($courseId), fn ($query) => $query->where('course_id', $courseId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('license_key', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($userQuery) use ($q) {
                            $userQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('mobile', 'like', "%{$q}%");
                        })
                        ->orWhereHas('course.product', function ($productQuery) use ($q) {
                            $productQuery->where('title', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $courses = Course::query()
            ->with('product')
            ->whereHas('product')
            ->orderByDesc('id')
            ->get();

        $counts = [
            'all' => SpotplayerLicense::query()->count(),
            'issued' => SpotplayerLicense::query()->where('status', SpotplayerLicense::STATUS_ISSUED)->count(),
            'pending' => SpotplayerLicense::query()->where('status', SpotplayerLicense::STATUS_PENDING)->count(),
            'failed' => SpotplayerLicense::query()->where('status', SpotplayerLicense::STATUS_FAILED)->count(),
        ];

        return view('admin.licenses.index', compact('licenses', 'courses', 'counts', 'status', 'courseId', 'q'));
    }

    public function show(SpotplayerLicense $license): View
    {
        $license->load(['user', 'course.product', 'order']);

        return view('admin.licenses.show', compact('license'));
    }

    public function reissue(SpotplayerLicense $license): RedirectResponse
    {
        $license->loadMissing(['user', 'course']);

        if (! $license->user || ! $license->course) {
            return back()->with('error', 'کاربر یا دوره این لایسنس پیدا نشد.');
        }

        $fresh = $this->spotPlayer->issueLicense(
            $license->user,
            $license->course,
            $license->order_id,
            force: true,
        );

        if ($fresh->status === SpotplayerLicense::STATUS_ISSUED) {
            return redirect()
                ->route('admin.licenses.show', $fresh)
                ->with('success', 'لایسنس با موفقیت صادر شد.');
        }

        return redirect()
            ->route('admin.licenses.show', $fresh)
            ->with('error', 'صدور لایسنس ناموفق بود. جزئیات در لاگ و پاسخ API آمده است.');
    }
}
