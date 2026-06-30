{{-- Auto-injected $archive (period + total) via View::composer. Optional $class. --}}
<ul class="{{ $class ?? 'blog__archive' }}">
    @foreach ($archive as $bucket)
        <li class="blog__archive-item">
            <a href="{{ $blogRoute('index', ['month' => $bucket->period]) }}">
                {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $bucket->period)->isoFormat('MMMM YYYY') }}
                <span class="blog__count">{{ $bucket->total }}</span>
            </a>
        </li>
    @endforeach
</ul>
