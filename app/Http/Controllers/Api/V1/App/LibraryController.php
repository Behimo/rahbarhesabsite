<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Api\V1\Library\RevealCourseLicenseAction;
use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EnrollmentResource;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\SpotplayerLicense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LibraryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $enrollments = CourseEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->with(['course.product'])
            ->latest('enrolled_at')
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        $licenses = SpotplayerLicense::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('course_id', $enrollments->getCollection()->pluck('course_id'))
            ->get(['id', 'course_id', 'status'])
            ->keyBy('course_id');

        $enrollments->getCollection()->each(function (CourseEnrollment $enrollment) use ($licenses) {
            $enrollment->setRelation('license', $licenses->get($enrollment->course_id));
        });

        return EnrollmentResource::collection($enrollments);
    }

    public function show(Request $request, Course $course): EnrollmentResource
    {
        $enrollment = CourseEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->with(['course.product'])
            ->firstOrFail();

        $license = SpotplayerLicense::query()
            ->where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->first(['id', 'course_id', 'status']);

        $enrollment->setRelation('license', $license);

        return new EnrollmentResource($enrollment);
    }

    public function license(Request $request, Course $course, RevealCourseLicenseAction $action): JsonResponse
    {
        try {
            $license = $action->handle($request->user(), $course, $request->ip());
        } catch (ApiException $exception) {
            $body = [
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
            ];

            if ($exception->errorCode === 'license_pending') {
                $body['data'] = ['status' => 'pending'];
            }

            return response()->json($body, $exception->status)->header('Cache-Control', 'no-store');
        }

        return (new LicenseResource($license))
            ->response()
            ->header('Cache-Control', 'no-store');
    }
}
