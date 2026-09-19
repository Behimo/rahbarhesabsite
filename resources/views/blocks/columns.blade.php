<section class="cms-block cms-columns mx-auto max-w-6xl px-4 py-8">
    <div class="grid gap-8 md:grid-cols-{{ max(count($columns), 1) }}">
        @foreach($columns as $column)
            <div class="cms-column flex flex-col gap-6">
                @foreach($column['sections'] ?? [] as $section)
                    @if(! empty(trim(strip_tags($section['html'] ?? ''))))
                        <div class="cms-column-section">
                            {!! $section['html'] ?? '' !!}
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</section>
