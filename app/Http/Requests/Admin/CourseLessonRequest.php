<?php

namespace App\Http\Requests\Admin;

use App\Rules\EnglishSlug;
use Illuminate\Foundation\Http\FormRequest;

class CourseLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:100', new EnglishSlug],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_provider' => ['nullable', 'in:aparat,youtube,vimeo,upload,spotplayer,download'],
            'download_url' => ['nullable', 'string', 'max:500'],
            'spotplayer_course_id' => ['nullable', 'string', 'max:100'],
            'spotplayer_item_id' => ['nullable', 'string', 'max:100'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_free_preview' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'عنوان درس',
            'slug' => 'نامک',
            'video_url' => 'آدرس ویدیو',
            'video_provider' => 'سرویس ویدیو',
            'duration_seconds' => 'مدت',
        ];
    }

    public function lessonAttributes(): array
    {
        $validated = $this->validated();
        $validated['is_free_preview'] = $this->boolean('is_free_preview');

        return $validated;
    }
}
