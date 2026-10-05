@extends('layouts.admin')

@section('title', 'درگاه‌های پرداخت')

@php
    $activeCount = collect($gateways)->where('enabled', true)->count();
    $gatewayCount = count($gateways);
@endphp

@section('lede', 'در صفحه خرید فقط درگاه‌های فعال نشان داده می‌شوند.')

@section('actions')
    @if ($gatewayCount > 0)
        <p class="gw-summary mb-0" data-gw-summary role="status" aria-atomic="true">{{ $activeCount }} از {{ $gatewayCount }} درگاه فعال است.</p>
    @endif
@endsection

@section('content')
<div class="gw">

    @if ($gatewayCount === 0)
        <div class="card">
            <div class="card-body text-muted">هنوز درگاهی ثبت نشده است.</div>
        </div>
    @else
        <form method="POST" action="{{ route('admin.gateways.update') }}" data-gw>
            @csrf
            @method('PUT')

            <div class="gw-list">
                @foreach ($gateways as $gateway)
                    @php
                        $enabled = filter_var(old('gateways.'.$gateway['name'], $gateway['enabled']), FILTER_VALIDATE_BOOLEAN);
                    @endphp
                    <article class="card gw-card" data-gw-card data-on="{{ $enabled ? '1' : '0' }}">
                        <div class="card-body">
                            <div class="gw-card__head">
                                <div>
                                    <h5 class="mb-1" id="gateway-title-{{ $gateway['name'] }}">{{ $gateway['label'] }}</h5>
                                    <div class="text-muted" dir="ltr">{{ $gateway['name'] }}</div>
                                </div>
                                <div class="gw-card__controls">
                                    <span class="badge {{ $enabled ? 'bg-label-success' : 'bg-label-secondary' }}" data-gw-badge>{{ $enabled ? 'فعال' : 'خاموش' }}</span>
                                    <div class="form-check form-switch gw-toggle mb-0">
                                        <input type="hidden" name="gateways[{{ $gateway['name'] }}]" value="0">
                                        <input
                                            type="checkbox"
                                            name="gateways[{{ $gateway['name'] }}]"
                                            value="1"
                                            class="form-check-input"
                                            id="gateway-{{ $gateway['name'] }}"
                                            role="switch"
                                            data-gw-toggle
                                            @checked($enabled)
                                        >
                                        <label class="form-check-label" for="gateway-{{ $gateway['name'] }}">
                                            <span class="visually-hidden">{{ $gateway['label'] }} </span>روشن
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @if (count($gateway['fields']) > 0)
                                <div class="gw-fields">
                                    @foreach ($gateway['fields'] as $field)
                                        @if ($field['type'] === 'boolean')
                                            <div class="gw-field gw-field--switch">
                                                <div class="form-check form-switch mb-1">
                                                    <input type="hidden" name="credentials[{{ $gateway['name'] }}][{{ $field['key'] }}]" value="0">
                                                    <input
                                                        type="checkbox"
                                                        name="credentials[{{ $gateway['name'] }}][{{ $field['key'] }}]"
                                                        value="1"
                                                        class="form-check-input"
                                                        id="gateway-{{ $gateway['name'] }}-{{ $field['key'] }}"
                                                        role="switch"
                                                        @checked(filter_var(old('credentials.'.$gateway['name'].'.'.$field['key'], $field['value']), FILTER_VALIDATE_BOOLEAN))
                                                    >
                                                    <label class="form-check-label" for="gateway-{{ $gateway['name'] }}-{{ $field['key'] }}">{{ $field['label'] }}</label>
                                                </div>
                                                <p class="gw-hint mb-0">پرداخت آزمایشی به درگاه واقعی وصل نمی‌شود.</p>
                                            </div>
                                        @else
                                            <div class="gw-field gw-field--text">
                                                <label class="form-label" for="gateway-{{ $gateway['name'] }}-{{ $field['key'] }}">{{ $field['label'] }}</label>
                                                <input
                                                    type="text"
                                                    name="credentials[{{ $gateway['name'] }}][{{ $field['key'] }}]"
                                                    id="gateway-{{ $gateway['name'] }}-{{ $field['key'] }}"
                                                    value="{{ old('credentials.'.$gateway['name'].'.'.$field['key'], $field['value']) }}"
                                                    class="form-control"
                                                    dir="ltr"
                                                    autocomplete="off"
                                                    spellcheck="false"
                                                >
                                            </div>
                                        @endif
                                    @endforeach
                                    @if (collect($gateway['fields'])->contains(fn ($field) => $field['type'] !== 'boolean'))
                                        <p class="gw-hint gw-hint--span mb-0">فیلد خالی، مقدار تنظیم سرور را نگه می‌دارد.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="gw-save">
                <div>
                    <p class="text-danger mb-1" data-gw-alert hidden>حداقل یک درگاه باید فعال باشد.</p>
                    <p class="text-muted mb-0" data-gw-dirty hidden>تغییرات هنوز در فروشگاه اعمال نشده.</p>
                </div>
                @if (admin_can('settings', 'update'))
                    <button type="submit" class="btn btn-primary" data-gw-submit>ذخیره درگاه‌ها</button>
                @endif
            </div>
        </form>
    @endif
</div>
@endsection

@section('page-style')
<style>
    .gw-summary {
        color: var(--bs-secondary-color);
        background: var(--bs-paper-bg, var(--bs-card-bg, #fff));
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        min-height: 2.75rem;
        padding: 0.4rem 0.85rem;
        line-height: 1.4;
    }

    .gw-list {
        display: grid;
        gap: 1rem;
        grid-template-columns: 1fr;
    }

    @media (min-width: 1400px) {
        .gw-list {
            grid-template-columns: 1fr 1fr;
        }
    }

    .gw-card {
        border-inline-start: 3px solid var(--bs-border-color);
    }

    .gw-card[data-on="1"] {
        border-inline-start-color: var(--bs-success);
    }

    .gw-card__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
    }

    .gw-card__controls {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .gw-toggle.form-switch {
        min-height: 44px;
        display: flex;
        align-items: center;
        padding-inline-start: 3.25rem;
        margin-bottom: 0;
    }

    .gw-toggle.form-switch .form-check-input {
        width: 2.75rem;
        height: 1.45rem;
        margin-top: 0;
        margin-left: 0;
        margin-right: 0;
        margin-inline-start: -3.25rem;
        cursor: pointer;
    }

    .gw-toggle .form-check-label {
        cursor: pointer;
        padding-inline-start: 0.35rem;
    }

    .gw-toggle .form-check-input:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 3px;
    }

    .gw-fields {
        display: grid;
        gap: 1rem;
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--bs-border-color);
    }

    .gw-hint {
        color: color-mix(in srgb, var(--bs-body-color) 70%, transparent);
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .gw-hint--span {
        grid-column: 1 / -1;
    }

    @media (min-width: 768px) {
        .gw-fields {
            grid-template-columns: 1fr 1fr;
        }

        .gw-field--switch,
        .gw-fields:not(:has(.gw-field--text + .gw-field--text)) .gw-field--text {
            grid-column: 1 / -1;
        }
    }

    .gw-save {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        margin-top: 1rem;
    }

    @media (max-width: 575.98px) {
        .gw-save .btn {
            width: 100%;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .gw-card {
            transition: none;
        }
    }
</style>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('[data-gw]');
        if (!form) {
            return;
        }

        var summary = document.querySelector('[data-gw-summary]');
        var dirty = form.querySelector('[data-gw-dirty]');
        var alertBox = form.querySelector('[data-gw-alert]');
        var submit = form.querySelector('[data-gw-submit]');
        var toggles = form.querySelectorAll('[data-gw-toggle]');

        function paint() {
            var on = 0;

            toggles.forEach(function (input) {
                var card = input.closest('[data-gw-card]');
                var active = input.checked;
                if (active) {
                    on += 1;
                }
                if (!card) {
                    return;
                }
                card.dataset.on = active ? '1' : '0';
                var badge = card.querySelector('[data-gw-badge]');
                if (!badge) {
                    return;
                }
                badge.textContent = active ? 'فعال' : 'خاموش';
                badge.classList.toggle('bg-label-success', active);
                badge.classList.toggle('bg-label-secondary', !active);
            });

            if (summary) {
                summary.textContent = on + ' از ' + toggles.length + ' درگاه فعال است.';
            }
        }

        form.addEventListener('change', function (event) {
            if (event.target.matches('[data-gw-toggle]')) {
                paint();
                if (alertBox) {
                    alertBox.hidden = true;
                }
            }
            if (dirty) {
                dirty.hidden = false;
            }
        });

        form.addEventListener('submit', function (event) {
            var on = 0;
            toggles.forEach(function (input) {
                if (input.checked) {
                    on += 1;
                }
            });
            if (on === 0) {
                event.preventDefault();
                if (alertBox) {
                    alertBox.hidden = false;
                }
                return;
            }
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'در حال ذخیره…';
            }
        });
    });
</script>
@endsection
