{{-- Auto-injected $tags via View::composer. Optional $class to restyle. --}}
<ul class="{{ $class ?? 'blog__tags' }}">
    @foreach ($tags as $tag)
        <li class="blog__tags-item">
            <a href="{{ $blogRoute('index', ['tag' => $tag->slug]) }}">#{{ $tag->name }}</a>
        </li>
    @endforeach
</ul>
