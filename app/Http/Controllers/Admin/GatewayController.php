<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class GatewayController extends Controller
{
    public function __construct(private PaymentGatewayRegistry $gateways) {}

    public function index(): View
    {
        return view('admin.gateways.index', [
            'gateways' => $this->gateways->panelRows(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $names = array_column($this->gateways->definitions(), 'name');

        $validated = $request->validate([
            'gateways' => ['required', 'array'],
            'gateways.*' => ['boolean'],
            'credentials' => ['nullable', 'array'],
        ]);

        $enabled = [];

        foreach ($names as $name) {
            $enabled[$name] = filter_var($validated['gateways'][$name] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        try {
            $this->gateways->syncEnabled($enabled);
            $this->gateways->syncCredentials(is_array($validated['credentials'] ?? null) ? $validated['credentials'] : []);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'درگاه‌های پرداخت ذخیره شد.');
    }
}
