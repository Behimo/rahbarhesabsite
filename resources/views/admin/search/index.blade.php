@extends('layouts.admin')
@section('title', 'جستجو')
@section('content')
<h4 class="mb-4">جستجوی داخلی</h4>
<form class="mb-4"><input name="q" value="{{ $q }}" class="form-control" placeholder="جستجو..."></form>
@if($q)
<div class="row g-4">
    <div class="col-md-4"><h5>صفحات</h5><ul>@foreach($pages as $p)<li><a href="{{ route('admin.pages.edit', $p) }}">{{ $p->title }}</a></li>@endforeach</ul></div>
    <div class="col-md-4"><h5>نوشته‌ها</h5><ul>@foreach($posts as $p)<li><a href="{{ route('admin.posts.edit', $p) }}">{{ $p->title }}</a></li>@endforeach</ul></div>
    <div class="col-md-4"><h5>دوره‌ها</h5><ul>@foreach($courses as $c)<li><a href="{{ route('admin.courses.edit', $c) }}">{{ $c->title }}</a></li>@endforeach</ul></div>
</div>
@endif
@endsection
