<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnswerCafeQuestionRequest;
use App\Http\Requests\Admin\RejectCafeQuestionRequest;
use App\Models\CafeQuestion;
use App\Notifications\QuestionAnsweredNotification;
use App\Notifications\QuestionRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class CafeQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $q = $request->string('q')->toString();

        $questions = CafeQuestion::query()
            ->with('user')
            ->when(
                $status !== '' && array_key_exists($status, CafeQuestion::statusLabels()),
                fn ($query) => $query->where('status', $status)
            )
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%'.$q.'%')
                        ->orWhere('body', 'like', '%'.$q.'%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            'status' => $status,
            'q' => $q,
            'statusLabels' => CafeQuestion::statusLabels(),
        ]);
    }

    public function show(CafeQuestion $question): View
    {
        $question->load(['user', 'answers.user']);

        return view('admin.questions.show', compact('question'));
    }

    public function answer(AnswerCafeQuestionRequest $request, CafeQuestion $question): RedirectResponse
    {
        $answered = DB::transaction(function () use ($request, $question): bool {
            $lockedQuestion = CafeQuestion::query()->lockForUpdate()->findOrFail($question->id);

            if (! $lockedQuestion->isPending()) {
                return false;
            }

            $lockedQuestion->answers()->create([
                'user_id' => $request->user()->id,
                'body' => $request->validated('body'),
                'is_official' => true,
            ]);

            $lockedQuestion->update([
                'status' => CafeQuestion::STATUS_ANSWERED,
                'answered_at' => now(),
                'rejection_reason' => null,
                'rejected_at' => null,
            ]);

            return true;
        });

        if (! $answered) {
            return back()->with('error', 'فقط سوالات در انتظار قابل پاسخ‌دهی هستند.');
        }

        $question->refresh();
        $question->user?->notify(new QuestionAnsweredNotification($question));

        return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'پاسخ ثبت شد و سوال منتشر گردید.');
    }

    public function reject(RejectCafeQuestionRequest $request, CafeQuestion $question): RedirectResponse
    {
        $rejected = DB::transaction(function () use ($request, $question): bool {
            $lockedQuestion = CafeQuestion::query()->lockForUpdate()->findOrFail($question->id);

            if (! $lockedQuestion->isPending()) {
                return false;
            }

            $lockedQuestion->update([
                'status' => CafeQuestion::STATUS_REJECTED,
                'rejection_reason' => $request->validated('rejection_reason'),
                'rejected_at' => now(),
            ]);

            return true;
        });

        if (! $rejected) {
            return back()->with('error', 'فقط سوالات در انتظار قابل رد هستند.');
        }

        $question->refresh();
        $question->user?->notify(new QuestionRejectedNotification($question));

        return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'سوال رد شد و به کاربر اطلاع داده شد.');
    }

    public function destroy(CafeQuestion $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.questions.index')->with('success', 'سوال حذف شد.');
    }
}
