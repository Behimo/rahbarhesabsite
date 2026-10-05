<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PopupRequest;
use App\Models\CmsPopup;
use App\Services\PopupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PopupController extends Controller
{
    public function __construct(private PopupService $popups) {}

    public function index(): View
    {
        $popups = CmsPopup::query()->orderBy('sort_order')->latest()->paginate(20);

        return view('admin.popups.index', compact('popups'));
    }

    public function create(): View
        {
            return view('admin.popups.form', [
                'popup' => new CmsPopup([
                    'is_active' => true,
                    'target_mode' => CmsPopup::MODE_PAGES,
                    'frequency' => 'session',
                    'audience' => 'all',
                    'delay_seconds' => 1,
                    'sort_order' => 0,
                    'priority' => 0,
                    'rules' => ['match' => 'all'],
                ]),
                'pageGroups' => $this->popups->pageGroups(),
            ]);
        }

    public function store(PopupRequest $request): RedirectResponse
    {
        CmsPopup::query()->create($request->popupAttributes());

        return redirect()->route('admin.popups.index')->with('success', 'پاپ‌آپ ایجاد شد.');
    }

    public function edit(CmsPopup $popup): View
    {
        return view('admin.popups.form', [
            'popup' => $popup,
            'pageGroups' => $this->popups->pageGroups(),
        ]);
    }

    public function update(PopupRequest $request, CmsPopup $popup): RedirectResponse
    {
        $popup->update($request->popupAttributes());

        return redirect()->route('admin.popups.index')->with('success', 'پاپ‌آپ به‌روزرسانی شد.');
    }

    public function destroy(CmsPopup $popup): RedirectResponse
    {
        $popup->delete();

        return redirect()->route('admin.popups.index')->with('success', 'پاپ‌آپ حذف شد.');
    }
}
