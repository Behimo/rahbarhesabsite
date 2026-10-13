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

    public function test_answers_increment_answers_count_for_official_and_community(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $owner = User::factory()->create();
        $communityUser = User::factory()->create();
        $question = $this->pendingQuestion($owner);

        $this->actingAs($admin)->post(route('admin.questions.answer', $question), [
            'body' => 'پاسخ کارشناسی معتبر و کامل برای سوال شما.',
        ])->assertRedirect();

        $question->refresh();
        $this->assertSame(1, $question->answers_count);
        $this->assertNotNull($question->officialAnswer);
        $this->assertSame($question->id, $question->officialAnswer->official_for_question_id);

        $this->actingAs($communityUser)->post(route('cafe-soal.answers.store', $question->slug), [
            'body' => 'پاسخ تکمیلی جامعه برای این پرسش منتشرشده.',
        ])->assertRedirect();

        $question->refresh();
        $this->assertSame(2, $question->answers_count);
    }

    public function test_second_official_answer_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $question = $this->answeredQuestion(User::factory()->create(), $admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        CafeAnswer::query()->create([
            'cafe_question_id' => $question->id,
            'user_id' => $admin->id,
            'body' => 'تلاش برای ثبت پاسخ رسمی دوم خارج از فلو ادمین.',
            'is_official' => true,
        ]);
    }

    public function test_views_and_answers_count_are_not_mass_assignable(): void
    {
        $owner = User::factory()->create();

        $question = CafeQuestion::query()->create([
            'user_id' => $owner->id,
            'title' => 'پرسش برای بررسی mass assignment',
            'slug' => 'mass-assign-'.$owner->id.'-'.uniqid(),
            'body' => 'متن کافی برای یک پرسش آزمایشی در کافه سوال.',
            'status' => CafeQuestion::STATUS_PENDING,
            'views_count' => 999,
            'answers_count' => 50,
        ]);

        $this->assertSame(0, $question->fresh()->views_count);
        $this->assertSame(0, $question->fresh()->answers_count);
    }

    public function test_admin_can_update_official_answer_after_publishing(): void
    {
        $admin = $this->makeAdmin();
        $question = $this->answeredQuestion(User::factory()->create(), $admin);

        $this->actingAs($admin)->put(route('admin.questions.update-answer', $question), [
            'body' => 'پاسخ رسمی ویرایش‌شده با جزئیات بیشتر برای کاربر.',
        ])->assertRedirect(route('admin.questions.show', $question));

        $this->assertDatabaseHas('cafe_answers', [
            'cafe_question_id' => $question->id,
            'is_official' => true,
            'body' => 'پاسخ رسمی ویرایش‌شده با جزئیات بیشتر برای کاربر.',
        ]);
    }

    public function test_admin_can_change_question_status_after_answer(): void
    {
        $admin = $this->makeAdmin();
        $question = $this->answeredQuestion(User::factory()->create(), $admin);

        $this->actingAs($admin)->patch(route('admin.questions.update-status', $question), [
            'status' => CafeQuestion::STATUS_PENDING,
        ])->assertRedirect(route('admin.questions.show', $question));

        $question->refresh();
        $this->assertSame(CafeQuestion::STATUS_PENDING, $question->status);
        $this->assertNull($question->answered_at);

        $this->actingAs($admin)->patch(route('admin.questions.update-status', $question), [
            'status' => CafeQuestion::STATUS_REJECTED,
            'rejection_reason' => 'پس از بررسی بیشتر، این پرسش رد شد.',
        ])->assertRedirect(route('admin.questions.show', $question));

        $question->refresh();
        $this->assertSame(CafeQuestion::STATUS_REJECTED, $question->status);
        $this->assertSame('پس از بررسی بیشتر، این پرسش رد شد.', $question->rejection_reason);
    }

    public function test_admin_can_delete_community_answers(): void
    {
        $admin = $this->makeAdmin();
        $communityUser = User::factory()->create();
        $question = $this->answeredQuestion(User::factory()->create(), $admin);

        $communityAnswer = $question->answers()->create([
            'user_id' => $communityUser->id,
            'body' => 'پاسخ کاربر جامعه که باید توسط ادمین حذف شود.',
            'is_official' => false,
        ]);

        $this->assertSame(2, $question->fresh()->answers_count);

        $this->actingAs($admin)->delete(route('admin.questions.answers.destroy', [$question, $communityAnswer]))
            ->assertRedirect(route('admin.questions.show', $question));

        $this->assertDatabaseMissing('cafe_answers', ['id' => $communityAnswer->id]);
        $this->assertSame(1, $question->fresh()->answers_count);
    }

    public function test_admin_cannot_delete_official_answer_via_community_route(): void
    {
        $admin = $this->makeAdmin();
        $question = $this->answeredQuestion(User::factory()->create(), $admin);
        $official = $question->officialAnswer;

        $this->actingAs($admin)->delete(route('admin.questions.answers.destroy', [$question, $official]))
            ->assertRedirect();

        $this->assertDatabaseHas('cafe_answers', [
            'id' => $official->id,
            'is_official' => true,
        ]);
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

    private function answeredQuestion(User $owner, User $admin): CafeQuestion
    {
        $question = $this->pendingQuestion($owner);

        $question->answers()->create([
            'user_id' => $admin->id,
            'body' => 'پاسخ رسمی اولیه برای سوال آزمایشی.',
            'is_official' => true,
        ]);

        $question->update([
            'status' => CafeQuestion::STATUS_ANSWERED,
            'answered_at' => now(),
        ]);

        return $question->fresh();
    }
}
