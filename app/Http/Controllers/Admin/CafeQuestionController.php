<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnswerCafeQuestionRequest;
use App\Http\Requests\Admin\RejectCafeQuestionRequest;
use App\Http\Requests\Admin\UpdateCafeQuestionRequest;
use App\Models\CafeAnswer;
use App\Models\CafeQuestion;
use App\Models\Category;
use App\Notifications\QuestionAnsweredNotification;
use App\Notifications\QuestionRejectedNotification;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class CafeQuestionController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $q = $request->string('q')->toString();
        $categoryId = $request->integer('category') ?: null;

        $questions = CafeQuestion::query()
            ->with(['user', 'categories'])
            ->when(
                $status !== '' && array_key_exists($status, CafeQuestion::statusLabels()),
                fn ($query) => $query->where('status', $status)
            )
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->whereHas('categories', fn ($inner) => $inner->where('categories.id', $categoryId));
            })
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
            'categoryId' => $categoryId,
            'cafeCategories' => $this->categories->flat(Category::TYPE_CAFE, true),
            'statusLabels' => CafeQuestion::statusLabels(),
        ]);
    }

    public function show(CafeQuestion $question): View
    {
        $question->load(['user', 'answers.user', 'categories']);

        return view('admin.questions.show', [
            'question' => $question,
            'cafeCategories' => $this->categories->flat(Category::TYPE_CAFE, true),
        ]);
    }

    public function update(UpdateCafeQuestionRequest $request, CafeQuestion $question): RedirectResponse
    {
        $categoryId = (int) $request->validated('category_id');
        $status = $request->validated('status');

        if ($status === CafeQuestion::STATUS_ANSWERED && ! $question->officialAnswer()->exists()) {
            return back()
                ->withInput()
                ->with('error', 'برای وضعیت «پاسخ داده‌شده» ابتدا باید پاسخ رسمی ثبت شود.');
        }

        $payload = match ($status) {
            CafeQuestion::STATUS_ANSWERED => [
                'status' => CafeQuestion::STATUS_ANSWERED,
                'answered_at' => $question->answered_at ?? now(),
                'rejection_reason' => null,
                'rejected_at' => null,
            ],
            CafeQuestion::STATUS_REJECTED => [
                'status' => CafeQuestion::STATUS_REJECTED,
                'rejection_reason' => $request->validated('rejection_reason'),
                'rejected_at' => now(),
                'answered_at' => null,
            ],
            default => [
                'status' => CafeQuestion::STATUS_PENDING,
                'rejection_reason' => null,
                'rejected_at' => null,
                'answered_at' => null,
            ],
        };

        DB::transaction(function () use ($question, $categoryId, $payload, $request): void {
            $question->syncCategories([$categoryId], $categoryId);
            $question->update($payload);

            $official = $question->officialAnswer;
            if ($official && $request->filled('body')) {
                $official->update([
                    'body' => $request->validated('body'),
                ]);
            }
        });

        return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'تغییرات ذخیره شد.');
    }

    public function answer(AnswerCafeQuestionRequest $request, CafeQuestion $question): RedirectResponse
    {
        $answered = DB::transaction(function () use ($request, $question): bool {
            $lockedQuestion = CafeQuestion::query()->lockForUpdate()->findOrFail($question->id);

            if (! $lockedQuestion->isPending()) {
                return false;
            }

            if ($request->filled('category_id')) {
                $categoryId = (int) $request->validated('category_id');
                $lockedQuestion->syncCategories([$categoryId], $categoryId);
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

            if ($request->filled('category_id')) {
                $categoryId = (int) $request->validated('category_id');
                $lockedQuestion->syncCategories([$categoryId], $categoryId);
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

    public function destroyAnswer(CafeQuestion $question, CafeAnswer $answer): RedirectResponse
    {
        if ($answer->cafe_question_id !== $question->id) {
            abort(404);
        }

        if ($answer->is_official) {
            return back()->with('error', 'پاسخ رسمی را نمی‌توان حذف کرد؛ متن آن را ویرایش کنید.');
        }

        $answer->delete();

        return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'پاسخ کاربر حذف شد.');
    }

    public function destroy(CafeQuestion $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.questions.index')->with('success', 'سوال حذف شد.');
    }
}
