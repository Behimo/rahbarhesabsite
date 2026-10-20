<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Sms\SmsConfigurationException;
use App\Services\Sms\SmsDriverRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SmsDriverController extends Controller
{
    public function __construct(private SmsDriverRegistry $drivers) {}

    public function index(): View
    {
        return view('admin.sms.index', [
            'drivers' => $this->drivers->panelRows(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $definitions = $this->drivers->definitions();
        $rules = [
            'default' => ['nullable', 'string', Rule::in(array_keys($definitions))],
            'enabled' => ['sometimes', 'array'],
            'settings' => ['sometimes', 'array'],
            'clear' => ['sometimes', 'array'],
        ];

        $attributes = [];
        foreach ($definitions as $name => $definition) {
            $rules["enabled.$name"] = ['sometimes', 'boolean'];
            $rules["settings.$name"] = ['sometimes', 'array'];
            $rules["clear.$name"] = ['sometimes', 'array'];

            foreach ($definition['fields'] as $key => $field) {
                $attributes["settings.$name.$key"] = $field['label'];
                $rules["settings.$name.$key"] = match ($field['type']) {
                    'secret' => ['nullable', 'string', 'max:512'],
                    'integer' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
                    'identifier' => ['nullable', 'string', 'max:64', 'regex:/\A[A-Za-z][A-Za-z0-9_]*\z/D'],
                    'text' => ['nullable', 'string', 'max:255'],
                };
                if ($field['type'] === 'secret') {
                    $rules["clear.$name.$key"] = ['sometimes', 'boolean'];
                }
            }
        }

        $validator = Validator::make($request->all(), $rules, [
            'regex' => ':attribute باید نام متغیر بدون علامت # و با همان حروف بزرگ و کوچک قالب باشد.',
        ], $attributes);
        if ($validator->fails()) {
            return $this->invalidInput($request, $validator->errors());
        }

        $validated = $validator->validated();

        try {
            $this->drivers->save(
                $validated['default'] ?? null,
                $request->input('enabled', []),
                $request->input('settings', []),
                $request->input('clear', []),
            );
        } catch (ValidationException $exception) {
            return $this->invalidInput($request, $exception->errors());
        } catch (SmsConfigurationException) {
            return back()->with('error', 'تنظیمات پیامک معتبر نیست. کلید ذخیره‌شده را دوباره وارد کنید.');
        }

        return back()->with('success', 'تنظیمات درایورهای پیامک ذخیره شد.');
    }

    private function invalidInput(Request $request, array|MessageBag $errors): RedirectResponse
    {
        $input = ['default' => is_string($request->input('default')) ? $request->input('default') : ''];
        foreach ($this->drivers->definitions() as $name => $definition) {
            $input['enabled'][$name] = $request->boolean("enabled.$name");
            foreach ($definition['fields'] as $key => $field) {
                if ($field['type'] === 'secret') {
                    continue;
                }

                $value = $request->input("settings.$name.$key");
                if (is_scalar($value) || $value === null) {
                    $input['settings'][$name][$key] = $value;
                }
            }
        }

        return back()->withErrors($errors)->withInput($input);
    }
}
