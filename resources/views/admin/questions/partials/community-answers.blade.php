@if ($communityAnswers->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">پاسخ‌های کاربران</h5></div>
        <div class="card-body">
            @foreach ($communityAnswers as $answer)
                <div class="mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                        <div>
                            <strong>{{ $answer->user?->displayName() ?? '—' }}</strong>
                            <small class="text-muted d-block">{{ $answer->created_at->format('Y/m/d H:i') }}</small>
                        </div>
                        @if (admin_can('questions', 'delete'))
                            <form method="POST" action="{{ route('admin.questions.answers.destroy', [$question, $answer]) }}" onsubmit="return confirm('این پاسخ حذف شود؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                            </form>
                        @endif
                    </div>
                    <div class="admin-prose">{{ $answer->body }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif
