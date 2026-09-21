@extends('layouts.admin')

@section('title', 'ریدایرکت‌ها')

@section('content')
<h4 class="mb-4">ریدایرکت‌های ۳۰۱/۳۰۲</h4>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-4">
    <div class="card-header">افزودن ریدایرکت</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.redirects.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">از مسیر</label>
                <input type="text" name="from_path" class="form-control" dir="ltr" placeholder="/old-url" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">به مسیر</label>
                <input type="text" name="to_path" class="form-control" dir="ltr" placeholder="/courses" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">کد</label>
                <select name="status_code" class="form-select">
                    <option value="301">301</option>
                    <option value="302">302</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="hidden" name="is_active" value="1">
                <button class="btn btn-primary w-100">افزودن</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">ورود دسته‌ای (هر خط: from to [code])</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.redirects.import') }}">
            @csrf
            <textarea name="rows" rows="5" class="form-control mb-3" dir="ltr" placeholder="/product/old /courses/new 301"></textarea>
            <button class="btn btn-outline-primary">ورود</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>از</th>
                    <th>به</th>
                    <th>کد</th>
                    <th>فعال</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($redirects as $redirect)
                    <tr>
                        <td colspan="5">
                            <form method="POST" action="{{ route('admin.redirects.update', $redirect) }}" class="row g-2 align-items-center">
                                @csrf @method('PUT')
                                <div class="col-md-4">
                                    <input type="text" name="from_path" value="{{ $redirect->from_path }}" class="form-control form-control-sm" dir="ltr">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="to_path" value="{{ $redirect->to_path }}" class="form-control form-control-sm" dir="ltr">
                                </div>
                                <div class="col-md-1">
                                    <select name="status_code" class="form-select form-select-sm">
                                        <option value="301" @selected($redirect->status_code == 301)>301</option>
                                        <option value="302" @selected($redirect->status_code == 302)>302</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($redirect->is_active)>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-sm btn-label-primary">ذخیره</button>
                            </form>
                            <form method="POST" action="{{ route('admin.redirects.destroy', $redirect) }}" class="d-inline" onsubmit="return confirm('حذف شود؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-label-danger">حذف</button>
                            </form>
                                </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">ریدایرکتی ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($redirects->hasPages())
        <div class="card-footer">{{ $redirects->links() }}</div>
    @endif
</div>
@endsection
