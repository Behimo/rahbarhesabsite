<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => [
                'contact_email' => CmsSetting::get('contact_email', config('cms.contact_email')),
                'contact_phone' => CmsSetting::get('contact_phone', config('cms.contact_phone')),
                'social_linkedin' => CmsSetting::get('social_linkedin', config('cms.social.linkedin')),
                'social_telegram' => CmsSetting::get('social_telegram', config('cms.social.telegram')),
                'social_instagram' => CmsSetting::get('social_instagram', config('cms.social.instagram')),
                'site_logo' => CmsSetting::get('site_logo', config('cms.branding.logo')),
                'site_favicon' => CmsSetting::get('site_favicon', config('cms.branding.favicon')),
                'site_og_image' => CmsSetting::get('site_og_image', config('cms.branding.og_image')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_email' => ['required', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'social_linkedin' => ['nullable', 'url', 'max:300'],
            'social_telegram' => ['nullable', 'url', 'max:300'],
            'social_instagram' => ['nullable', 'url', 'max:300'],
            'site_logo' => ['nullable', 'string', 'max:500'],
            'site_favicon' => ['nullable', 'string', 'max:500'],
            'site_og_image' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated as $key => $value) {
            CmsSetting::set($key, $value);
        }

        return back()->with('success', 'تنظیمات ذخیره شد.');
    }
}
