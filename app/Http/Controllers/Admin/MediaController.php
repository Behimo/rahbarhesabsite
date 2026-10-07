<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Models\CmsMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $media = CmsMedia::query()->latest()->paginate(24);

        if ($request->expectsJson() || $request->boolean('json')) {
            return response()->json([
                'data' => $media->getCollection()->map(fn (CmsMedia $item) => [
                    'id' => $item->id,
                    'url' => $item->url(),
                    'filename' => $item->filename,
                    'alt' => $item->alt,
                    'is_image' => $item->isImage(),
                ]),
                'next_page_url' => $media->nextPageUrl(),
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
            ]);
        }

        return view('admin.media.index', compact('media'));
    }

    public function store(MediaUploadRequest $request): JsonResponse|RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store('cms/'.date('Y/m'), 'public');

        $media = CmsMedia::query()->create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $this->safeOriginalName($file),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt' => $request->input('alt'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'url' => $media->url(),
                'id' => $media->id,
                'filename' => $media->filename,
            ]);
        }

        return back()->with('success', 'فایل آپلود شد.');
    }

    public function destroy(CmsMedia $medium): RedirectResponse
    {
        Storage::disk($medium->disk)->delete($medium->path);
        $medium->delete();

        return back()->with('success', 'فایل حذف شد.');
    }

    private function safeOriginalName(\Illuminate\Http\UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = preg_replace('/[^\p{L}\p{N}.\- _]/u', '', $name) ?? '';
        $name = trim($name, '. ');

        return $name !== '' ? mb_substr($name, 0, 180) : $file->hashName();
    }
}
