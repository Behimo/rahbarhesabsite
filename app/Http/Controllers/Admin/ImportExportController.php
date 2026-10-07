<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CmsImportRequest;
use App\Services\ImportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;

class ImportExportController extends Controller
{
    public function __construct(private ImportExportService $service) {}

    public function export()
    {
        $payload = $this->service->export();

        return Response::json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="cms-export-'.now()->format('Y-m-d').'.json"',
        ]);
    }

    public function import(CmsImportRequest $request): RedirectResponse
    {
        $payload = json_decode(file_get_contents($request->file('export_file')->getRealPath()), true);

        if (! is_array($payload)) {
            return back()->withErrors(['export_file' => 'فایل نامعتبر است.']);
        }

        try {
            $this->service->import($payload);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['export_file' => $e->getMessage()]);
        } catch (\Throwable) {
            return back()->withErrors(['export_file' => 'درون‌ریزی انجام نشد.']);
        }

        return back()->with('success', 'درون‌ریزی انجام شد.');
    }
}
