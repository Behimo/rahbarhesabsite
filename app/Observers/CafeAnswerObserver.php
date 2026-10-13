<?php

namespace App\Observers;

use App\Models\CafeAnswer;
use App\Models\CafeQuestion;
use Illuminate\Validation\ValidationException;

class CafeAnswerObserver
{
    public function creating(CafeAnswer $answer): void
    {
        $this->syncOfficialMarker($answer);
        $this->assertSingleOfficial($answer);
    }

    public function updating(CafeAnswer $answer): void
    {
        $this->syncOfficialMarker($answer);

        if ($answer->isDirty(['is_official', 'cafe_question_id'])) {
            $this->assertSingleOfficial($answer);
        }
    }

    public function created(CafeAnswer $answer): void
    {
        CafeQuestion::query()->whereKey($answer->cafe_question_id)->increment('answers_count');
    }

    public function deleted(CafeAnswer $answer): void
    {
        CafeQuestion::query()
            ->whereKey($answer->cafe_question_id)
            ->where('answers_count', '>', 0)
            ->decrement('answers_count');
    }

    private function syncOfficialMarker(CafeAnswer $answer): void
    {
        $answer->official_for_question_id = $answer->is_official
            ? $answer->cafe_question_id
            : null;
    }

    private function assertSingleOfficial(CafeAnswer $answer): void
    {
        if (! $answer->is_official || $answer->cafe_question_id === null) {
            return;
        }

        $exists = CafeAnswer::query()
            ->where('cafe_question_id', $answer->cafe_question_id)
            ->where('is_official', true)
            ->when($answer->exists, fn ($query) => $query->whereKeyNot($answer->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'body' => 'برای این سوال قبلاً پاسخ رسمی ثبت شده است.',
            ]);
        }
    }
}
