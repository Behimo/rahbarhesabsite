<?php

namespace App\View\Components\Admin;

use App\Support\Admin\AdminFeedback;
use App\Support\Admin\FeedbackNotice;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Feedback extends Component
{
    /** @var list<FeedbackNotice> */
    public array $notices;

    public function __construct(AdminFeedback $feedback)
    {
        $this->notices = $feedback->notices();
    }

    public function render(): View
    {
        return view('components.admin.feedback');
    }

    public function shouldRender(): bool
    {
        return $this->notices !== [];
    }
}
