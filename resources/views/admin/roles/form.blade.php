@extends('layouts.admin')

@section('title', $role->exists ? 'ویرایش نقش' : 'نقش جدید')

@section('heading', $role->exists ? 'ویرایش: '.$role->displayName() : 'نقش جدید')

@section('lede', $role->is_system
    ? 'شناسه نقش‌های سیستمی ثابت است. عنوان و دسترسی‌ها را می‌توانید تغییر دهید.'
    : 'عنوان در فهرست کاربران دیده می‌شود. شناسه فقط برای سیستم است و بعداً در کد استفاده می‌شود.')

@section('content')
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="card">
    @csrf
    @if ($role->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="role-label">عنوان نقش *</label>
                <input id="role-label" type="text" name="label" value="{{ old('label', $role->label) }}" class="form-control @error('label') is-invalid @enderror" required>
                @error('label')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role-name">شناسه *</label>
                <input id="role-name" type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" dir="ltr" placeholder="support" @readonly($role->is_system) required>
                <div class="form-text">حروف کوچک انگلیسی، عدد و زیرخط. مثال: support</div>
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <fieldset class="col-12">
                <legend class="form-label float-none w-auto px-0">دسترسی‌های نقش</legend>
                @php $selected = old('permissions', $selectedPermissions); @endphp
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="role-permissions-all">
                    <label class="form-check-label" for="role-permissions-all">انتخاب همه</label>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="permissions[]" value="{{ \App\Support\AccessCatalog::ACCESS_ADMIN }}" class="form-check-input" id="role_perm_access_admin" data-role-permission
                        @checked(in_array(\App\Support\AccessCatalog::ACCESS_ADMIN, $selected, true))>
                    <label class="form-check-label" for="role_perm_access_admin">ورود به پنل مدیریت</label>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
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
                                                    <label class="form-check-label" for="role_perm_{{ $perm }}">{{ $actions[$action] }}</label>
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
                @error('permissions')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </fieldset>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary">ذخیره</button>
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
