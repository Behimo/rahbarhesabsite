<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_email' => ['required', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'social_linkedin' => ['nullable', 'url', 'max:300'],
            'social_telegram' => ['nullable', 'url', 'max:300'],
            'social_instagram' => ['nullable', 'url', 'max:300'],
            'site_logo' => ['nullable', 'string', 'max:500'],
            'site_favicon' => ['nullable', 'string', 'max:500'],
            'site_og_image' => ['nullable', 'string', 'max:500'],
            'top_bar_enabled' => ['nullable', 'boolean'],
            'top_bar_text' => ['nullable', 'string', 'max:300'],
            'top_bar_button_text' => ['nullable', 'string', 'max:80'],
            'top_bar_button_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'top_bar_enabled' => $this->boolean('top_bar_enabled'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'contact_email' => 'ایمیل تماس',
            'contact_phone' => 'تلفن تماس',
            'social_linkedin' => 'لینکدین',
            'social_telegram' => 'تلگرام',
            'social_instagram' => 'اینستاگرام',
            'site_logo' => 'لوگو',
            'site_favicon' => 'فاوآیکون',
            'site_og_image' => 'تصویر اشتراک‌گذاری',
            'top_bar_enabled' => 'نمایش نوار بالای هدر',
            'top_bar_text' => 'متن نوار بالای هدر',
            'top_bar_button_text' => 'متن دکمه نوار بالای هدر',
            'top_bar_button_url' => 'لینک دکمه نوار بالای هدر',
        ];
    }
}
