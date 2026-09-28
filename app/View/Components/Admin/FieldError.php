<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FieldError extends Component
{
    public function __construct(public string $name) {}

    public function render(): View
    {
        return view('components.admin.field-error');
    }
}
