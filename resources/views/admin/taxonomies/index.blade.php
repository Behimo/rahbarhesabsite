@extends('layouts.admin')
@section('title', 'دسته‌بندی‌ها')
@section('content')
<h4 class="mb-4">Taxonomy و دسته‌بندی</h4>
@foreach($taxonomies as $taxonomy)
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">{{ $taxonomy->name }} ({{ $taxonomy->slug }})</h5></div>
    <div class="card-body">
        <ul class="mb-3">@foreach($taxonomy->terms as $term)<li>{{ $term->name }} <form class="d-inline" method="POST" action="{{ route('admin.taxonomies.terms.destroy', $term) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0">حذف</button></form></li>@endforeach</ul>
        <form method="POST" action="{{ route('admin.taxonomies.terms.store', $taxonomy) }}" class="row g-2">
            @csrf
            <div class="col-md-4"><input name="name" class="form-control" placeholder="نام" required></div>
            <div class="col-md-4"><input name="slug" class="form-control" dir="ltr" placeholder="slug" required></div>
            <div class="col-md-4"><button class="btn btn-primary w-100">افزودن</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection
