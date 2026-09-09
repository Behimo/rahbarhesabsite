@extends('layouts.admin')
@section('title', 'افزونه‌ها')
@section('content')
<h4 class="mb-4">مدیریت افزونه‌ها</h4>
<div class="card mb-4"><div class="card-body">
    <form method="POST" action="{{ route('admin.plugins.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-md-8"><input type="file" name="plugin_zip" class="form-control" accept=".zip" required></div>
        <div class="col-md-4"><button class="btn btn-primary w-100">نصب افزونه</button></div>
    </form>
</div></div>
<table class="table card-table">
    <thead><tr><th>نام</th><th>نسخه</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
        @foreach($plugins as $plugin)
            <tr>
                <td>{{ $plugin->name }}</td>
                <td>{{ $plugin->version }}</td>
                <td>{{ $plugin->is_active ? 'فعال' : 'غیرفعال' }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.plugins.toggle', $plugin) }}">@csrf
                        <button class="btn btn-sm btn-outline-primary">{{ $plugin->is_active ? 'غیرفعال' : 'فعال' }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
