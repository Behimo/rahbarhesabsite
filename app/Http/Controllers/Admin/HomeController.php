<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\HomeContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private HomeContentService $homeContent) {}

    public function edit(): View|\Illuminate\Http\RedirectResponse
    {
        $homePage = CmsPage::query()->where('slug', 'home')->first();

        if ($homePage) {
            return redirect()->route('admin.pages.builder', $homePage);
        }

        return view('admin.home.edit', [
            'content' => $this->homeContent->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_eyebrow' => ['nullable', 'string', 'max:200'],
            'hero_title_line1' => ['nullable', 'string', 'max:100'],
            'hero_rotate_words' => ['nullable', 'string'],
            'hero_title_line2' => ['nullable', 'string', 'max:100'],
            'hero_lead' => ['nullable', 'string', 'max:500'],
            'hero_cta_primary' => ['nullable', 'string', 'max:50'],
            'hero_cta_secondary' => ['nullable', 'string', 'max:80'],
            'hero_chips' => ['nullable', 'string'],
            'hero_pills' => ['nullable', 'array'],
            'hero_pills.*.title' => ['nullable', 'string', 'max:80'],
            'hero_pills.*.text' => ['nullable', 'string', 'max:200'],
            'stats' => ['nullable', 'array'],
            'stats.*.value' => ['nullable', 'string', 'max:30'],
            'stats.*.label' => ['nullable', 'string', 'max:50'],
            'stats.*.hint' => ['nullable', 'string', 'max:80'],
            'partners' => ['nullable', 'string'],
            'testimonials' => ['nullable', 'array'],
            'testimonials.*.name' => ['nullable', 'string', 'max:80'],
            'testimonials.*.role' => ['nullable', 'string', 'max:120'],
            'testimonials.*.text' => ['nullable', 'string', 'max:500'],
            'cta_title' => ['nullable', 'string', 'max:100'],
            'cta_subtitle' => ['nullable', 'string', 'max:300'],
        ]);

        $content = [
            'hero' => [
                'eyebrow' => $validated['hero_eyebrow'] ?? '',
                'title_line1' => $validated['hero_title_line1'] ?? '',
                'rotate_words' => array_values(array_filter(array_map('trim', explode("\n", $validated['hero_rotate_words'] ?? '')))),
                'title_line2' => $validated['hero_title_line2'] ?? '',
                'lead' => $validated['hero_lead'] ?? '',
                'cta_primary' => $validated['hero_cta_primary'] ?? '',
                'cta_secondary' => $validated['hero_cta_secondary'] ?? '',
                'chips' => array_values(array_filter(array_map('trim', explode("\n", $validated['hero_chips'] ?? '')))),
                'pills' => array_values(array_filter($validated['hero_pills'] ?? [], fn ($p) => ! empty($p['title']))),
            ],
            'stats' => array_values(array_filter($validated['stats'] ?? [], fn ($s) => ! empty($s['value']))),
            'partners' => array_values(array_filter(array_map('trim', explode("\n", $validated['partners'] ?? '')))),
            'testimonials' => array_values(array_filter($validated['testimonials'] ?? [], fn ($t) => ! empty($t['text']))),
            'cta' => [
                'title' => $validated['cta_title'] ?? '',
                'subtitle' => $validated['cta_subtitle'] ?? '',
            ],
        ];

        $this->homeContent->save($content);

        return back()->with('success', 'صفحه اصلی ذخیره شد.');
    }
}
