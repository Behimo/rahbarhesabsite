<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['export_file' => ['required', 'file', 'mimes:json,txt']]);
        $payload = json_decode(file_get_contents($request->file('export_file')->getRealPath()), true);

        if (! is_array($payload)) {
            return back()->withErrors(['export_file' => 'فایل نامعتبر است.']);
        }

        $this->service->import($payload);

        return back()->with('success', 'درون‌ریزی انجام شد.');
    }
}
