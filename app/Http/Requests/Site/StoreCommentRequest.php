<?php

namespace App\Http\Requests\Site;

use App\Services\Comments\CommentSettings;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(CommentSettings::class);
        $min = $settings->int('min_length', 3);
        $max = $settings->int('max_length', 2000);
        $authRequired = $settings->bool('auth_required', true);

        $rules = [
            'body' => ['required', 'string', 'min:'.$min, 'max:'.$max],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'website' => ['nullable', 'string', 'max:200'], // honeypot
            'commentable_type' => ['required', 'string', 'in:post,product'],
            'commentable_id' => ['required', 'integer'],
        ];

        if (! $authRequired && ! $this->user()) {
            $rules['author_name'] = ['required', 'string', 'max:120'];
            $rules['author_email'] = ['required', 'email', 'max:190'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'body.required' => 'متن دیدگاه الزامی است.',
            'author_name.required' => 'نام الزامی است.',
            'author_email.required' => 'ایمیل الزامی است.',
        ];
    }
}
