<?php

namespace App\Http\Controllers\Site;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends SiteController
{
    public function index(): View
    {
        $seo = $this->seo->forPage('contact');

        return $this->render('pages.contact', [
                    'seo' => $seo,
                    'contact' => $this->siteData->contact(),
                    'faq' => $this->siteData->contactFaq(),
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'تماس با ما', 'url' => route('contact')],
                ]),
                $this->seo->webPageSchema($seo['title'], $seo['description'], route('contact')),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'name.required' => 'نام الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'ایمیل معتبر نیست.',
            'message.required' => 'پیام الزامی است.',
        ]);

        ContactMessage::query()->create($validated);

        return back()->with('success', 'پیام شما با موفقیت ارسال شد. در ۲۴ ساعت پاسخ می‌دهیم.');
    }
}
