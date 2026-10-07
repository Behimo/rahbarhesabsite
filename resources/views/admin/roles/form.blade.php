@extends('layouts.admin')

@section('title', $role->exists ? 'ویرایش نقش' : 'نقش جدید')

@section('heading', $role->exists ? 'ویرایش: '.$role->displayName() : 'نقش جدید')

@section('lede', $role->is_system
    ? 'شناسه نقش‌های سیستمی ثابت است. عنوان و دسترسی‌ها را می‌توانید عوض کنید.'
    : 'عنوان در فهرست کاربران دیده می‌شود. شناسه فقط برای سیستم است.')

@section('vendor-style')
@include('admin.partials.composer-styles')
@endsection

@section('content')
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
    @csrf
    @if ($role->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">دسترسی‌ها</h5></div>
                <div class="card-body">
                    @php $selected = old('permissions', $selectedPermissions); @endphp
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="role-permissions-all">
                        <label class="form-check-label" for="role-permissions-all">انتخاب همه</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="permissions[]" value="{{ \App\Support\AccessCatalog::ACCESS_ADMIN }}" class="form-check-input" id="role_perm_access_admin" data-role-permission
                            @checked(in_array(\App\Support\AccessCatalog::ACCESS_ADMIN, $selected, true))>
                        <label class="form-check-label" for="role_perm_access_admin">ورود به پنل مدیریت</label>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-2">
                            <thead>
                                <tr>
                                    <th>بخش</th>
                                    @foreach (\App\Support\AccessCatalog::actionLabels() as $actionLabel)
                                        <th>{{ $actionLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Support\AccessCatalog::groups() as $group => $meta)
                                    @php $actions = \App\Support\AccessCatalog::groupActions($group); @endphp
                                    <tr>
                                        <th scope="row">{{ $meta['label'] }}</th>
                                        @foreach (\App\Support\AccessCatalog::actionLabels() as $action => $actionLabel)
                                            <td>
                                                @if (isset($actions[$action]))
                                                    @php $perm = \App\Support\AccessCatalog::permission($group, $action); @endphp
                                                    <div class="form-check mb-0">
                                                        <input type="checkbox" name="permissions[]" value="{{ $perm }}" class="form-check-input" id="role_perm_{{ $perm }}" data-role-permission data-permission-group="{{ $group }}" data-permission-action="{{ $action }}"
                                                            @checked(in_array($perm, $selected, true))>
                                                        <label class="visually-hidden" for="role_perm_{{ $perm }}">{{ $meta['label'] }} — {{ $actions[$action] }}</label>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text">با انتخاب ایجاد، ویرایش یا حذف، نمایش همان بخش هم فعال می‌شود.</div>
                    @error('permissions') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="col-xl-4 admin-form-side">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">نقش</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="role-label">عنوان *</label>
                        <input id="role-label" type="text" name="label" value="{{ old('label', $role->label) }}" class="form-control @error('label') is-invalid @enderror" required>
                        @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role-name">شناسه *</label>
                        <input id="role-name" type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" dir="ltr" placeholder="support" @readonly($role->is_system) required>
                        <div class="form-text">حروف کوچک انگلیسی، عدد و زیرخط.</div>
                        @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ذخیره</button>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
document.getElementById('role-permissions-all')?.addEventListener('change', (event) => {
    document.querySelectorAll('[data-role-permission]').forEach((input) => {
        input.checked = event.target.checked;
    });
});

document.querySelectorAll('[data-permission-action]').forEach((input) => {
    input.addEventListener('change', () => {
        const group = input.dataset.permissionGroup;
        const view = document.querySelector(`[data-permission-group="${group}"][data-permission-action="view"]`);

        if (input.dataset.permissionAction !== 'view' && input.checked && view) {
            view.checked = true;
        }

        if (input.dataset.permissionAction === 'view' && ! input.checked) {
            document.querySelectorAll(`[data-permission-group="${group}"]`).forEach((other) => {
                other.checked = false;
            });
        }
    });
});
</script>
@endsection
