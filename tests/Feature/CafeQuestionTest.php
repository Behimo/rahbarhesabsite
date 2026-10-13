<?php

namespace Tests\Feature;

use App\Models\CafeAnswer;
use App\Models\CafeQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CafeQuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_question_for_review(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cafe-soal.store'), [
            'title' => 'چگونه مالیات محاسبه می‌شود؟',
            'body' => 'برای محاسبه مالیات سالانه چه مواردی را باید در نظر بگیرم؟',
        ])->assertRedirect(route('cafe-soal.index'));

        $this->assertDatabaseHas('cafe_questions', [
            'user_id' => $user->id,
            'status' => CafeQuestion::STATUS_PENDING,
        ]);
    }

    public function test_admin_answer_publishes_question_and_notifies_owner(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $owner = User::factory()->create();
        $question = $this->pendingQuestion($owner);

        $this->actingAs($admin)->post(route('admin.questions.answer', $question), [
            'body' => 'پاسخ کارشناسی معتبر و کامل برای سوال شما.',
        ])->assertRedirect(route('admin.questions.show', $question));

        $this->assertDatabaseHas('cafe_questions', [
            'id' => $question->id,
            'status' => CafeQuestion::STATUS_ANSWERED,
        ]);
        $this->assertDatabaseHas('cafe_answers', [
            'cafe_question_id' => $question->id,
            'is_official' => true,
        ]);
        Notification::assertSentTo($owner, \App\Notifications\QuestionAnsweredNotification::class);
    }

    public function test_admin_cannot_answer_question_more_than_once(): void
    {
        $admin = $this->makeAdmin();
        $question = $this->pendingQuestion(User::factory()->create());

        $this->actingAs($admin)->post(route('admin.questions.answer', $question), [
            'body' => 'پاسخ کارشناسی معتبر و کامل برای سوال شما.',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.questions.answer', $question), [
            'body' => 'تلاش برای ثبت پاسخ رسمی دوم به این سوال.',
        ])->assertRedirect();

        $this->assertSame(1, CafeAnswer::query()->where('cafe_question_id', $question->id)->where('is_official', true)->count());
    }

    public function test_admin_rejection_stores_reason_and_notifies_owner(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $owner = User::factory()->create();
        $question = $this->pendingQuestion($owner);

        $this->actingAs($admin)->post(route('admin.questions.reject', $question), [
            'rejection_reason' => 'این پرسش خارج از موضوع خدمات است.',
        ])->assertRedirect(route('admin.questions.show', $question));

        $this->assertDatabaseHas('cafe_questions', [
            'id' => $question->id,
            'status' => CafeQuestion::STATUS_REJECTED,
            'rejection_reason' => 'این پرسش خارج از موضوع خدمات است.',
        ]);
        Notification::assertSentTo($owner, \App\Notifications\QuestionRejectedNotification::class);
    }

    public function test_user_cannot_answer_unpublished_question(): void
    {
        $user = User::factory()->create();
        $question = $this->pendingQuestion(User::factory()->create());

        $this->actingAs($user)->post(route('cafe-soal.answers.store', $question->slug), [
            'body' => 'پاسخ کاربر به پرسشی که هنوز منتشر نشده است.',
        ])->assertNotFound();

        $this->assertDatabaseCount('cafe_answers', 0);
    }

    private function pendingQuestion(User $owner): CafeQuestion
    {
        return CafeQuestion::query()->create([
            'user_id' => $owner->id,
            'title' => 'پرسش آزمایشی درباره حسابداری',
            'slug' => 'question-'.$owner->id.'-'.uniqid(),
            'body' => 'متن کافی برای یک پرسش آزمایشی در کافه سوال.',
            'status' => CafeQuestion::STATUS_PENDING,
        ]);
    }
}
