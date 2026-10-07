<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
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

    public function store(ProductRequest $request): RedirectResponse
    {
        $validated = $request->productAttributes();
        $validated['dashboard_image'] = $this->resolveDashboardImage($request);

        CmsProduct::query()->create($validated);
        $this->siteData->clearCache();

        return redirect()->route('admin.products.index')->with('success', 'محصول ایجاد شد.');
    }

    public function edit(CmsProduct $product): View
    {
        return view('admin.products.form', compact('product'));
    }

    public function update(ProductRequest $request, CmsProduct $product): RedirectResponse
    {
        $validated = $request->productAttributes();
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
        $extension = strtolower((string) $file->guessExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (! in_array($extension, $allowed, true)) {
            throw new \RuntimeException('فرمت تصویر داشبورد مجاز نیست.');
        }

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

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
