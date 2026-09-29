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
        ContactMessage::query()->create($request->messageAttributes());

        return back()->with('success', 'درخواست شما ثبت شد. کارشناسان مجموعه در ساعات کاری با شما تماس می‌گیرند.');
    }
}
