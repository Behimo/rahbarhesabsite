@extends('layouts.admin')
@section('title', 'جستجو')
@section('lede', 'صفحات، نوشته‌ها و دوره‌ها')
@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="{{ route('admin.search') }}" class="row g-3 align-items-end">
            <div class="col-md-8 col-lg-6">
                <label class="form-label" for="admin-search-q">عبارت</label>
                <input id="admin-search-q" name="q" value="{{ $q }}" class="form-control">
            </div>
            <div class="col-md-4 col-lg-3">
                <button type="submit" class="btn btn-primary">جستجو</button>
            </div>
        </form>
    </div>
</div>
@if($q)
<div class="row g-4">
    <div class="col-md-4"><h5>صفحات</h5><ul>@foreach($pages as $p)<li><a href="{{ route('admin.pages.edit', $p) }}">{{ $p->title }}</a></li>@endforeach</ul></div>
    <div class="col-md-4"><h5>نوشته‌ها</h5><ul>@foreach($posts as $p)<li><a href="{{ route('admin.posts.edit', $p) }}">{{ $p->title }}</a></li>@endforeach</ul></div>
    <div class="col-md-4"><h5>دوره‌ها</h5><ul>@foreach($courses as $c)<li><a href="{{ route('admin.courses.edit', $c) }}">{{ $c->title }}</a></li>@endforeach</ul></div>
</div>
@endif
@endsection
