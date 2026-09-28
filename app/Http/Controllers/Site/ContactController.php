<?php

namespace App\Http\Controllers\Site;

use App\Http\Requests\Site\ContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends SiteController
{
    public function index(): View
    {
        $seo = $this->seo->forPage('contact');

        return $this->renderSystemPage('contact', 'pages.contact', [
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

    public function store(ContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::query()->create($request->validated());

        return back()->with('success', 'پیام شما با موفقیت ارسال شد. در ۲۴ ساعت پاسخ می‌دهیم.');
    }
}
