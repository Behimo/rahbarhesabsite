@extends('layouts.admin')
@section('title', 'منوها')
@section('actions')
    <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i>منوی جدید
    </a>
@endsection
@section('content')
<div class="card">
<div class="table-responsive">
<table class="table table-hover mb-0">
    <thead><tr><th>نام</th><th>slug</th><th>محل</th><th>آیتم‌ها</th><th></th></tr></thead>
    <tbody>
        @foreach($menus as $menu)
            <tr>
                <td>{{ $menu->name }}</td>
                <td dir="ltr">{{ $menu->slug }}</td>
                <td>{{ $menu->location }}</td>
                <td>{{ $menu->all_items_count }}</td>
                <td><a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm btn-label-primary">ویرایش</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
</div>
@endsection
