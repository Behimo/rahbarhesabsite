@extends('layouts.admin')

@section('title', $type === 'post' ? 'دسته‌های بلاگ' : 'دسته‌های محصول')

@section('content')
@include('admin.partials.category-ui')

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h4 class="mb-1">{{ $type === 'post' ? 'دسته‌های بلاگ' : 'دسته‌های محصول و دوره' }}</h4>
        <p class="text-muted mb-0">
            {{ $type === 'post'
                ? 'هر مطلب یک دستهٔ اصلی دارد. زیردسته در فیلتر بلاگ، مطالب فرزند را هم نشان می‌دهد.'
                : 'هر دوره می‌تواند در چند دسته باشد. یکی را به‌عنوان دستهٔ اصلی برای مسیر صفحه انتخاب کنید.' }}
        </p>
    </div>
    <a href="{{ route('admin.categories.create', ['type' => $type]) }}" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i>دسته جدید
    </a>
</div>

<div class="card">
    @if ($categories->isEmpty())
        <div class="card-body text-center py-5">
            <p class="mb-3">هنوز دسته‌ای در این بخش نیست.</p>
            <a href="{{ route('admin.categories.create', ['type' => $type]) }}" class="btn btn-primary">اولین دسته را بسازید</a>
        </div>
    @else
        <div class="cat-tree">
            @foreach ($categories as $category)
                <div class="cat-node" style="--depth: {{ $category->treeDepth }}">
                    @if ($category->treeDepth > 0)
                        <span class="cat-node__rail" aria-hidden="true"></span>
                    @endif
                    <div class="cat-node__main flex-grow-1">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-medium">{{ $category->name }}</div>
                                <div class="cat-path">/{{ $category->full_slug }}</div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @unless ($category->is_active)
                                    <span class="badge bg-label-secondary">غیرفعال</span>
                                @endunless
                                <span class="badge bg-label-primary">{{ $category->categoryables_count }} {{ $type === 'post' ? 'مطلب' : 'محصول' }}</span>
                                <a href="{{ route('admin.categories.edit', ['category' => $category, 'type' => $type]) }}" class="btn btn-sm btn-label-primary">ویرایش</a>
                                <a href="{{ route('admin.categories.create', ['type' => $type, 'parent_id' => $category->id]) }}" class="btn btn-sm btn-label-secondary">زیردسته</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('این دسته حذف شود؟ زیردسته‌ها یک سطح بالا می‌آیند.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-label-danger">حذف</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
