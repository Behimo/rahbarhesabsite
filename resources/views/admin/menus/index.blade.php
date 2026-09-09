@extends('layouts.admin')
@section('title', 'منوها')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4 class="mb-0">منوساز</h4>
    <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">منوی جدید</a>
</div>
<table class="table card-table">
    <thead><tr><th>نام</th><th>slug</th><th>محل</th><th>آیتم‌ها</th><th></th></tr></thead>
    <tbody>
        @foreach($menus as $menu)
            <tr>
                <td>{{ $menu->name }}</td>
                <td dir="ltr">{{ $menu->slug }}</td>
                <td>{{ $menu->location }}</td>
                <td>{{ $menu->all_items_count }}</td>
                <td><a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm btn-outline-primary">ویرایش</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
