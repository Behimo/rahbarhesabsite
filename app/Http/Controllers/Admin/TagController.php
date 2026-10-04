<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::query()->withCount('posts')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        Tag::query()->create($this->validateTag($request));

        return back()->with('success', 'برچسب ایجاد شد.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $tag->update($this->validateTag($request, $tag));

        return back()->with('success', 'برچسب به‌روزرسانی شد.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return back()->with('success', 'برچسب حذف شد.');
    }

    /** @return array<string, mixed> */
    private function validateTag(Request $request, ?Tag $tag = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('tags', 'slug')->ignore($tag?->id),
            ],
        ]);
    }
}
