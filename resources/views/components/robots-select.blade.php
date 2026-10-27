@props(['value' => null, 'id' => 'robots'])
@php
    $selected = old('robots', $value ?: 'index, follow');
    $selected = is_string($selected) ? $selected : 'index, follow';
    $options = array_unique([...\App\Support\RobotsDirectives::OPTIONS, $selected]);
@endphp
<label class="form-label" for="{{ $id }}">ربات‌های جستجو</label>
<select name="robots" id="{{ $id }}" {{ $attributes->class(['form-select']) }}>
    @foreach ($options as $option)
        <option value="{{ $option }}" @selected($selected === $option)>{{ $option }}</option>
    @endforeach
</select>
