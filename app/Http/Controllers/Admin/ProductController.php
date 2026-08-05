<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsProduct;
use App\Services\SiteDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private SiteDataService $siteData) {}

    public function index(): View
    {
        $products = CmsProduct::query()->orderBy('sort_order')->get();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new CmsProduct]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $validated['dashboard_image'] = $this->resolveDashboardImage($request);

        CmsProduct::query()->create($validated);
        $this->siteData->clearCache();

        return redirect()->route('admin.products.index')->with('success', 'محصول ایجاد شد.');
    }

    public function edit(CmsProduct $product): View
    {
        return view('admin.products.form', compact('product'));
    }

    public function update(Request $request, CmsProduct $product): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $validated['dashboard_image'] = $this->resolveDashboardImage($request, $product);

        $product->update($validated);
        $this->siteData->clearCache();

        return redirect()->route('admin.products.index')->with('success', 'محصول به‌روزرسانی شد.');
    }

    public function destroy(CmsProduct $product): RedirectResponse
    {
        $this->deleteStoredImage($product->dashboard_image);
        $product->delete();
        $this->siteData->clearCache();

        return redirect()->route('admin.products.index')->with('success', 'محصول حذف شد.');
    }

    private function validateProduct(Request $request): array
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'accent' => ['required', 'in:orange,purple,blue,green'],
            'visual' => ['nullable', 'string', 'max:50'],
            'audience' => ['nullable', 'string', 'max:200'],
            'features' => ['nullable', 'string'],
            'cta' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:300'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'dashboard_image' => ['nullable', 'string', 'max:500'],
            'dashboard_image_file' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['features'] = array_values(array_filter(
            array_map('trim', explode("\n", $validated['features'] ?? ''))
        ));
        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_featured'] = $request->boolean('is_featured');

        unset($validated['dashboard_image_file']);

        return $validated;
    }

    private function resolveDashboardImage(Request $request, ?CmsProduct $product = null): ?string
    {
        if ($request->boolean('remove_dashboard_image')) {
            $this->deleteStoredImage($product?->dashboard_image);

            return null;
        }

        if ($request->hasFile('dashboard_image_file')) {
            $this->deleteStoredImage($product?->dashboard_image);

            return $this->storeDashboardImage($request->file('dashboard_image_file'), $request->input('slug'));
        }

        if ($request->filled('dashboard_image')) {
            $url = trim($request->input('dashboard_image'));

            if ($product && $url !== $product->dashboard_image) {
                $this->deleteStoredImage($product->dashboard_image);
            }

            return $url;
        }

        return $product?->dashboard_image;
    }

    private function storeDashboardImage(UploadedFile $file, string $slug): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'webp';

        return $file->storeAs('cms/products', $slug.'-dashboard.'.$extension, 'public');
    }

    private function deleteStoredImage(?string $image): void
    {
        if (! $image || str_starts_with($image, 'http') || str_starts_with($image, '/')) {
            return;
        }

        Storage::disk('public')->delete($image);
    }
}
