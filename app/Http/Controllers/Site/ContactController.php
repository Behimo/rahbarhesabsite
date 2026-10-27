<?php

namespace App\Http\Controllers\Site;

use App\Http\Requests\Site\ContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;

class ContactController extends SiteController
{
    public function store(ContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::query()->create($request->messageAttributes());

        return back()->with('success', 'درخواست شما ثبت شد. کارشناسان مجموعه در ساعات کاری با شما تماس می‌گیرند.');
    }
}
