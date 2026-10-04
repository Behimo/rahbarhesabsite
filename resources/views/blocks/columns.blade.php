<section class="rh-block-columns mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="grid gap-8 md:grid-cols-{{ max(count($columns), 1) }}">
        @foreach($columns as $column)
            <div class="rh-card p-6 flex flex-col gap-4">
                @foreach($column['sections'] ?? [] as $section)
                    @if(! empty(trim(strip_tags($section['html'] ?? ''))))
                        <div>{!! $section['html'] ?? '' !!}</div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</section>
