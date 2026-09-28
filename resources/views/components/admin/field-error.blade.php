@error($name)
    <div {{ $attributes->merge(['class' => 'invalid-feedback d-block']) }}>{{ $message }}</div>
@enderror
